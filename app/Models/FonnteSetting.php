<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FonnteSetting extends Model
{
    protected $fillable = [
        'is_active',
        'api_url',
        'token',
        'admin_phone',
        'send_customer_order_created',
        'send_admin_order_created',
        'customer_order_created_template',
        'admin_order_created_template',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_customer_order_created' => 'boolean',
        'send_admin_order_created' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_active' => false,
            'api_url' => 'https://api.fonnte.com/send',
            'send_customer_order_created' => true,
            'send_admin_order_created' => true,
            'customer_order_created_template' => static::defaultCustomerOrderTemplate(),
            'admin_order_created_template' => static::defaultAdminOrderTemplate(),
        ]);
    }

    public static function defaultCustomerOrderTemplate(): string
    {
        return "Halo {customer_name}, pesanan kamu berhasil dibuat.\n\n"
            . "No Order: {order_number}\n"
            . "Total: {total_amount}\n"
            . "Metode Pembayaran: {payment_method}\n"
            . "Metode Pengiriman: {shipping_method}\n\n"
            . "Detail Produk:\n{items}\n\n"
            . "Alamat:\n{shipping_address}\n\n"
            . "Link Pembayaran:\n{payment_url}\n\n"
            . "Terima kasih sudah belanja di Compify.";
    }

    public static function defaultAdminOrderTemplate(): string
    {
        return "Order baru masuk.\n\n"
            . "No Order: {order_number}\n"
            . "Customer: {customer_name}\n"
            . "Phone: {customer_phone}\n"
            . "Email: {customer_email}\n"
            . "Total: {total_amount}\n\n"
            . "Produk:\n{items}\n\n"
            . "Alamat:\n{shipping_address}\n\n"
            . "Payment URL:\n{payment_url}";
    }
}