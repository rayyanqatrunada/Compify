<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Support\OrderPaymentStatus;
use App\Support\OrderStatus;
use App\Support\MidtransPaymentChannel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderPaymentStatusService
{
    public function __construct(
        private readonly OrderInventoryService $inventoryService,
        private readonly UniversalDiscountService $discountService,
    ) {}

    public function handleMidtransNotification(array $payload): Order
    {
        $this->assertValidMidtransSignature($payload);

        $orderNumber = (string) Arr::get($payload, 'order_id');

        /** @var Order|null $order */
        $order = Order::query()->where('order_number', $orderNumber)->first();

        if (! $order) {
            throw new NotFoundHttpException('Order tidak ditemukan.');
        }

        return $this->applyMidtransPayload($order, $payload, 'midtrans_webhook');
    }

    public function applyMidtransPayload(Order $order, array|object $payload, string $source = 'midtrans_sync'): Order
    {
        $payload = json_decode(json_encode($payload), true) ?: [];

        $oldPaymentStatus = $order->payment_status;
        $oldOrderStatus = $order->order_status;

        $transactionStatus = strtolower((string) Arr::get($payload, 'transaction_status', ''));
        $fraudStatus = strtolower((string) Arr::get($payload, 'fraud_status', ''));
        $paymentType = Arr::get($payload, 'payment_type') ?: $order->payment_type;
        $transactionId = Arr::get($payload, 'transaction_id') ?: $order->payment_reference;
        $actualChannel = MidtransPaymentChannel::actualCodeFromPayload($payload);
        $selectedChannel = $order->payment_channel ?: $order->paymentMethod?->midtrans_channel_code ?: $actualChannel;
        $selectedChannelLabel = $order->payment_channel_label ?: MidtransPaymentChannel::label($selectedChannel);
        $midtransBank = MidtransPaymentChannel::bankFromPayload($payload);
        $midtransVaNumber = MidtransPaymentChannel::vaNumberFromPayload($payload);

        $baseUpdate = [
            'payment_type' => 'midtrans_snap',
            'payment_gateway' => 'midtrans',
            'payment_channel' => $selectedChannel,
            'payment_channel_label' => $selectedChannelLabel,
            'midtrans_payment_type' => $paymentType,
            'midtrans_transaction_id' => $transactionId,
            'midtrans_bank' => $midtransBank,
            'midtrans_va_number' => $midtransVaNumber,
            'payment_reference' => $transactionId,
            'payment_notification_payload' => $payload,
        ];

        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
            $updated = $this->markPaid($order, $baseUpdate);

            return $this->recordStatusChange(
                $updated,
                $oldPaymentStatus,
                $oldOrderStatus,
                $source,
                'Pembayaran Midtrans terkonfirmasi lunas.',
                $payload
            );
        }

        if ($transactionStatus === 'pending') {
            $order->forceFill([
                ...$baseUpdate,
                'payment_status' => OrderPaymentStatus::PENDING,
            ])->save();

            $updated = $order->fresh();

            return $this->recordStatusChange(
                $updated,
                $oldPaymentStatus,
                $oldOrderStatus,
                $source,
                'Status Midtrans masih pending.',
                $payload
            );
        }

        if ($transactionStatus === 'expire') {
            $updated = $this->markFailedOrExpired($order, OrderPaymentStatus::EXPIRED, $baseUpdate);

            return $this->recordStatusChange(
                $updated,
                $oldPaymentStatus,
                $oldOrderStatus,
                $source,
                'Pembayaran Midtrans expired.',
                $payload
            );
        }

        if (in_array($transactionStatus, ['cancel', 'deny'], true)) {
            $updated = $this->markFailedOrExpired($order, OrderPaymentStatus::CANCELLED, $baseUpdate);

            return $this->recordStatusChange(
                $updated,
                $oldPaymentStatus,
                $oldOrderStatus,
                $source,
                'Pembayaran Midtrans dibatalkan/ditolak.',
                $payload
            );
        }

        if (in_array($transactionStatus, ['failure', 'failed'], true)) {
            $updated = $this->markFailedOrExpired($order, OrderPaymentStatus::FAILED, $baseUpdate);

            return $this->recordStatusChange(
                $updated,
                $oldPaymentStatus,
                $oldOrderStatus,
                $source,
                'Pembayaran Midtrans gagal.',
                $payload
            );
        }

        if (str_starts_with($transactionStatus, 'refund')) {
            $updated = $this->markFailedOrExpired($order, OrderPaymentStatus::REFUNDED, $baseUpdate);

            return $this->recordStatusChange(
                $updated,
                $oldPaymentStatus,
                $oldOrderStatus,
                $source,
                'Pembayaran Midtrans refund.',
                $payload
            );
        }

        Log::warning('Midtrans notification received with unmapped status.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payload' => $payload,
        ]);

        $order->forceFill($baseUpdate)->save();

        $updated = $order->fresh();

        return $this->recordStatusChange(
            $updated,
            $oldPaymentStatus,
            $oldOrderStatus,
            $source,
            'Status Midtrans diterima, tetapi belum dipetakan otomatis.',
            $payload
        );
    }

    public function markPaid(Order $order, array $extra = []): Order
    {
        return DB::transaction(function () use ($order, $extra) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->forceFill([
                ...$extra,
                'payment_status' => OrderPaymentStatus::PAID,
                'order_status' => $lockedOrder->order_status === OrderStatus::PENDING
                    ? OrderStatus::PROCESSING
                    : $lockedOrder->order_status,
                'paid_at' => $lockedOrder->paid_at ?: now(),
            ])->save();

            if ((float) $lockedOrder->universal_discount_amount > 0) {
                $this->discountService->recordUsage($lockedOrder, (array) $lockedOrder->universal_discount_snapshot);
            }

            return $lockedOrder->fresh();
        });
    }

    public function markFailedOrExpired(Order $order, string $paymentStatus, array $extra = []): Order
    {
        $updated = DB::transaction(function () use ($order, $paymentStatus, $extra) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->forceFill([
                ...$extra,
                'payment_status' => $paymentStatus,
                'order_status' => in_array($paymentStatus, [OrderPaymentStatus::EXPIRED, OrderPaymentStatus::CANCELLED], true)
                    ? OrderStatus::CANCELLED
                    : $lockedOrder->order_status,
                'cancelled_at' => in_array($paymentStatus, [OrderPaymentStatus::EXPIRED, OrderPaymentStatus::CANCELLED], true)
                    ? ($lockedOrder->cancelled_at ?: now())
                    : $lockedOrder->cancelled_at,
            ])->save();

            return $lockedOrder->fresh();
        });

        if (in_array($paymentStatus, [OrderPaymentStatus::FAILED, OrderPaymentStatus::EXPIRED, OrderPaymentStatus::CANCELLED, OrderPaymentStatus::REFUNDED], true)) {
            $this->inventoryService->restore($updated);
        }

        return $updated->fresh();
    }

    private function recordStatusChange(
        Order $order,
        ?string $oldPaymentStatus,
        ?string $oldOrderStatus,
        string $source,
        ?string $note = null,
        array $payload = []
    ): Order {
        $order = $order->fresh();

        if ($oldPaymentStatus === $order->payment_status && $oldOrderStatus === $order->order_status) {
            return $order;
        }

        OrderStatusLog::create([
            'order_id' => $order->id,
            'user_id' => auth('admin')->id(),
            'source' => $source,
            'old_payment_status' => $oldPaymentStatus,
            'new_payment_status' => $order->payment_status,
            'old_order_status' => $oldOrderStatus,
            'new_order_status' => $order->order_status,
            'note' => $note,
            'meta' => [
                'transaction_status' => Arr::get($payload, 'transaction_status'),
                'fraud_status' => Arr::get($payload, 'fraud_status'),
                'payment_type' => Arr::get($payload, 'payment_type'),
                'payment_channel' => MidtransPaymentChannel::actualCodeFromPayload($payload),
                'transaction_id' => Arr::get($payload, 'transaction_id'),
                'status_code' => Arr::get($payload, 'status_code'),
            ],
        ]);

        return $order;
    }

    private function assertValidMidtransSignature(array $payload): void
    {
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '') {
            throw new AccessDeniedHttpException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $signature = (string) Arr::get($payload, 'signature_key', '');
        $orderId = (string) Arr::get($payload, 'order_id', '');
        $statusCode = (string) Arr::get($payload, 'status_code', '');
        $grossAmount = (string) Arr::get($payload, 'gross_amount', '');

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (! hash_equals($expected, $signature)) {
            throw new AccessDeniedHttpException('Signature Midtrans tidak valid.');
        }
    }
}
