<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_methods')->updateOrInsert(
            ['slug' => 'midtrans-credit-card'],
            [
                'name' => 'Kartu Kredit / Debit',
                'type' => 'api',
                'api_provider' => 'midtrans',
                'api_endpoint' => null,
                'description' => 'Bayar menggunakan kartu Visa, Mastercard, JCB, atau kartu lain yang aktif di Midtrans.',
                'midtrans_enabled_payments' => json_encode(['credit_card']),
                'instructions' => 'Pembayaran kartu diproses otomatis melalui Midtrans. Setelah pembayaran berhasil, status order akan diperbarui otomatis.',
                'is_active' => false,
                'sort_order' => 30,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('payment_methods')
            ->where('slug', 'midtrans-credit-card')
            ->delete();
    }
};
