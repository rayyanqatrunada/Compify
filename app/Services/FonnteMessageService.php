<?php

namespace App\Services;

use App\Models\FonnteMessageLog;
use App\Models\FonnteSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FonnteMessageService
{
    public function sendOrderCreatedNotifications(Order $order): void
    {
        $setting = FonnteSetting::current();

        if (! $setting->is_active) {
            return;
        }

        if (blank($setting->token)) {
            $this->logFailed($order, 'config_error', null, 'Token Fonnte belum diisi.');
            return;
        }

        $order->loadMissing([
            'items',
            'paymentMethod',
            'shippingMethod',
        ]);

        if ($setting->send_customer_order_created && filled($order->customer_phone)) {
            $message = $this->renderTemplate(
                $setting->customer_order_created_template ?: FonnteSetting::defaultCustomerOrderTemplate(),
                $order
            );

            $this->sendMessage(
                order: $order,
                eventType: 'customer_order_created',
                target: $order->customer_phone,
                message: $message,
                setting: $setting
            );
        }

        if ($setting->send_admin_order_created && filled($setting->admin_phone)) {
            $message = $this->renderTemplate(
                $setting->admin_order_created_template ?: FonnteSetting::defaultAdminOrderTemplate(),
                $order
            );

            $this->sendMessage(
                order: $order,
                eventType: 'admin_order_created',
                target: $setting->admin_phone,
                message: $message,
                setting: $setting
            );
        }
    }

    public function sendMessage(
        Order $order,
        string $eventType,
        string $target,
        string $message,
        ?FonnteSetting $setting = null
    ): bool {
        $setting ??= FonnteSetting::current();

        $normalizedTarget = $this->normalizePhone($target);

        $log = FonnteMessageLog::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'target' => $normalizedTarget,
            'status' => 'pending',
            'message' => $message,
        ]);

        if (! $setting->is_active) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Fonnte sedang nonaktif.',
            ]);

            return false;
        }

        if (blank($setting->token)) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Token Fonnte belum diisi.',
            ]);

            return false;
        }

        if (blank($normalizedTarget)) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Nomor target kosong atau tidak valid.',
            ]);

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->withHeaders([
                    'Authorization' => $setting->token,
                ])
                ->post($setting->api_url ?: 'https://api.fonnte.com/send', [
                    'target' => $normalizedTarget,
                    'message' => $message,
                ]);

            $responseData = $response->json();

            if ($response->successful() && (bool) data_get($responseData, 'status', false)) {
                $log->update([
                    'status' => 'success',
                    'response_data' => $responseData,
                    'sent_at' => now(),
                ]);

                return true;
            }

            $log->update([
                'status' => 'failed',
                'response_data' => $responseData ?: [
                    'body' => $response->body(),
                    'status_code' => $response->status(),
                ],
                'error_message' => data_get($responseData, 'reason')
                    ?: data_get($responseData, 'message')
                    ?: 'Request Fonnte gagal.',
            ]);

            return false;
        } catch (\Throwable $e) {
            report($e);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function renderTemplate(string $template, Order $order): string
    {
        $order->loadMissing([
            'items',
            'paymentMethod',
            'shippingMethod',
        ]);

        $replacements = [
            '{order_number}' => $order->order_number ?? '-',
            '{customer_name}' => $order->customer_name ?: 'Customer',
            '{customer_phone}' => $order->customer_phone ?: '-',
            '{customer_email}' => $order->customer_email ?: '-',

            '{items}' => $this->orderItemsText($order),

            '{subtotal}' => $this->formatRupiah($order->subtotal),
            '{shipping_cost}' => $this->formatRupiah($order->shipping_cost),
            '{discount_amount}' => $this->formatRupiah($order->discount_amount),
            '{total_amount}' => $this->formatRupiah($order->total_amount),

            '{payment_method}' => $order->paymentMethod?->name ?? '-',
            '{shipping_method}' => $order->shippingMethod?->name ?? '-',

            '{shipping_address}' => $this->shippingAddressText($order),

            '{payment_url}' => $order->payment_redirect_url ?: route('checkout.payment', $order),
            '{order_status}' => $order->order_status ?: 'pending',
            '{payment_status}' => $order->payment_status ?: 'pending',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    public function orderItemsText(Order $order): string
    {
        if ($order->items->isEmpty()) {
            return '-';
        }

        return $order->items
            ->map(function ($item, int $index) {
                $number = $index + 1;

                return "{$number}. {$item->product_name} x{$item->quantity} = {$this->formatRupiah($item->total)}";
            })
            ->implode("\n");
    }

    public function shippingAddressText(Order $order): string
    {
        return collect([
            $order->shipping_address,
            $order->shipping_district,
            $order->shipping_city,
            $order->shipping_province,
            $order->shipping_postal_code,
        ])
            ->filter()
            ->implode(', ');
    }

    public function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($phone === '') {
            return '';
        }

        if (Str::startsWith($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (Str::startsWith($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }

    public function formatRupiah(int|float|string|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    private function logFailed(Order $order, string $eventType, ?string $target, string $message): void
    {
        FonnteMessageLog::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'target' => $target,
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}