<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use RuntimeException;
use App\Support\MidtransPaymentChannel;

class MidtransPaymentService
{
    public function __construct()
    {
        $this->configure();
    }

    public function configure(): void
    {
        $serverKey = config('midtrans.server_key');

        if (! $serverKey) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diisi di .env.');
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapTransaction(Order $order): object
    {
        $order->loadMissing([
            'items',
            'paymentMethod',
            'shippingMethod',
        ]);

        return Snap::createTransaction(
            $this->buildSnapParams($order)
        );
    }

    public function createSnapRedirectUrl(Order $order): ?string
    {
        $transaction = $this->createSnapTransaction($order);

        return $transaction->redirect_url ?? null;
    }

    public function createSnapToken(Order $order): ?string
    {
        return Snap::getSnapToken(
            $this->buildSnapParams($order)
        );
    }

    public function getStatus(Order|string $order): object
    {
        $orderId = $order instanceof Order
            ? $order->order_number
            : $order;

        return Transaction::status($orderId);
    }

    public function buildSnapParams(Order $order): array
    {
        $grossAmount = (int) round((float) $order->total_amount);

        if ($grossAmount < 1) {
            throw new RuntimeException('Total order tidak valid untuk Midtrans.');
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $grossAmount,
            ],

            'customer_details' => [
                'first_name' => $order->customer_name ?: 'Customer',
                'email' => $order->customer_email ?: null,
                'phone' => $this->normalizePhone($order->customer_phone),
                'billing_address' => $this->addressPayload($order),
                'shipping_address' => $this->addressPayload($order),
            ],

            'item_details' => $this->itemDetails($order, $grossAmount),

            'custom_field1' => $order->payment_channel ?: $order->paymentMethod?->midtrans_channel_code,
            'custom_field2' => $order->payment_channel_label ?: $order->paymentMethod?->midtrans_channel_label,

            /*
            |--------------------------------------------------------------------------
            | Finish URL
            |--------------------------------------------------------------------------
            | Setelah pembayaran selesai di halaman Midtrans, customer bisa diarahkan
            | kembali ke payment detail order di website.
            */
            'callbacks' => [
                'finish' => route('checkout.payment', $order),
            ],
        ];

        $enabledPayments = MidtransPaymentChannel::enabledPayments(
            $order->paymentMethod?->midtrans_enabled_payments
        );

        if ($enabledPayments !== []) {
            $params['enabled_payments'] = $enabledPayments;
        }

        return $params;
    }

    private function itemDetails(Order $order, int $grossAmount): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $quantity = max(1, (int) $item->quantity);
            $price = (int) round((float) $item->price);

            if ($price < 1) {
                continue;
            }

            $items[] = [
                'id' => 'item-' . $item->id,
                'price' => $price,
                'quantity' => $quantity,
                'name' => $this->limitName($item->product_name ?: 'Produk'),
            ];
        }

        if ((float) $order->shipping_cost > 0) {
            $items[] = [
                'id' => 'shipping',
                'price' => (int) round((float) $order->shipping_cost),
                'quantity' => 1,
                'name' => 'Ongkir',
            ];
        }

        $sum = collect($items)->sum(function (array $item) {
            return (int) $item['price'] * (int) $item['quantity'];
        });

        if ($sum === $grossAmount) {
            return $items;
        }

        if ($sum < $grossAmount) {
            $items[] = [
                'id' => 'adjustment',
                'price' => $grossAmount - $sum,
                'quantity' => 1,
                'name' => 'Penyesuaian',
            ];

            return $items;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        | Kalau total item lebih besar dari gross amount karena pembulatan atau
        | perubahan data, pakai 1 item ringkasan agar request Midtrans tetap aman.
        */
        return [
            [
                'id' => 'order-' . $order->id,
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => $this->limitName('Order ' . $order->order_number),
            ],
        ];
    }

    private function addressPayload(Order $order): array
    {
        return [
            'first_name' => $order->customer_name ?: 'Customer',
            'phone' => $this->normalizePhone($order->customer_phone),
            'address' => $order->shipping_address ?: '-',
            'city' => $order->shipping_city ?: '-',
            'postal_code' => $order->shipping_postal_code ?: '',
            'country_code' => 'IDN',
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $number = preg_replace('/[^0-9]/', '', $phone);

        if (! $number) {
            return null;
        }

        if (str_starts_with($number, '0')) {
            return '62' . substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62' . $number;
        }

        return $number;
    }

    private function limitName(string $name): string
    {
        return mb_substr($name, 0, 50);
    }
}