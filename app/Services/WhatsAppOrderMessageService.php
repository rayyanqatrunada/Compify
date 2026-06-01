<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;

class WhatsAppOrderMessageService
{
    public function urlForOrder(Order $order, PaymentMethod $paymentMethod): ?string
    {
        $number = $paymentMethod->clean_whatsapp_number;

        if (! $number) {
            return null;
        }

        $message = $this->messageForOrder($order, $paymentMethod);

        return $this->buildWhatsappUrl($number, $message);
    }

    public function messageForOrder(Order $order, PaymentMethod $paymentMethod): string
    {
        $order->loadMissing([
            'items',
            'paymentMethod',
            'shippingMethod',
        ]);

        $template = $paymentMethod->whatsapp_template ?: $this->defaultTemplate();

        $replace = [
            '{order_number}' => $order->order_number ?? ('#' . $order->id),
            '{customer_name}' => $order->customer_name ?? '-',
            '{customer_phone}' => $order->customer_phone ?? '-',
            '{customer_email}' => $order->customer_email ?? '-',
            '{items}' => $this->itemsText($order),
            '{subtotal}' => $this->formatRupiah($order->subtotal),
            '{shipping_cost}' => $this->formatRupiah($order->shipping_cost),
            '{discount_amount}' => $this->formatRupiah($order->discount_amount),
            '{total_amount}' => $this->formatRupiah($order->total_amount),
            '{shipping_address}' => $this->shippingAddressText($order),
            '{payment_method}' => $paymentMethod->name,
            '{shipping_method}' => $order->shippingMethod?->name ?? '-',
        ];

        return trim(str_replace(array_keys($replace), array_values($replace), $template));
    }

    public function customerUrlForOrder(Order $order): ?string
    {
        $number = $this->cleanPhoneNumber($order->customer_phone);

        if (! $number) {
            return null;
        }

        return $this->buildWhatsappUrl($number, $this->customerMessageForOrder($order));
    }

    public function customerMessageForOrder(Order $order): string
    {
        $order->loadMissing([
            'items',
            'paymentMethod',
            'shippingMethod',
        ]);

        $orderNumber = $order->order_number ?? ('#' . $order->id);
        $customerName = $order->customer_name ?: 'Customer';

        return trim(
            "Halo {$customerName}, pesanan Anda dengan nomor {$orderNumber} sudah kami terima.\n\n" .
            "Total: {$this->formatRupiah($order->total_amount)}\n" .
            "Status pembayaran: " . ucfirst($order->payment_status ?? 'pending') . "\n" .
            "Status order: " . ucfirst($order->order_status ?? 'pending') . "\n\n" .
            "Jika sudah melakukan pembayaran, silakan kirim bukti pembayaran di chat ini. Terima kasih."
        );
    }

    public function defaultTemplate(): string
    {
        return "Halo Admin Compify, saya ingin konfirmasi pesanan.\n\nOrder: {order_number}\nNama: {customer_name}\nNo. HP: {customer_phone}\nEmail: {customer_email}\n\nItem:\n{items}\n\nSubtotal: {subtotal}\nOngkir: {shipping_cost}\nTotal: {total_amount}\n\nAlamat:\n{shipping_address}\n\nMetode pembayaran: {payment_method}\nMetode pengiriman: {shipping_method}\n\nTerima kasih.";
    }

    public function buildWhatsappUrl(string $number, string $message): string
    {
        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }

    public function cleanPhoneNumber(?string $phone): ?string
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

    private function itemsText(Order $order): string
    {
        return $order->items
            ->map(function ($item, int $index) {
                $number = $index + 1;

                $line = "{$number}. {$item->quantity}x {$item->product_name} - {$this->formatRupiah($item->total)}";

                if ($item->price_label) {
                    $line .= " ({$item->price_label})";
                }

                $children = collect($item->snapshot_data['children'] ?? []);

                if ($children->isNotEmpty()) {
                    $childText = $children
                        ->map(function ($child) {
                            $qty = $child['total_quantity'] ?? $child['quantity_per_package'] ?? 1;
                            $name = $child['name'] ?? 'Produk paket';

                            return "   - {$qty}x {$name}";
                        })
                        ->implode("\n");

                    $line .= "\n" . $childText;
                }

                return $line;
            })
            ->implode("\n");
    }

    private function shippingAddressText(Order $order): string
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

    private function formatRupiah(int|float|string|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}