<?php

use App\Support\MidtransPaymentChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_methods', 'description')) {
                $table->text('description')->nullable()->after('payment_url');
            }

            if (! Schema::hasColumn('payment_methods', 'midtrans_enabled_payments')) {
                $table->json('midtrans_enabled_payments')->nullable()->after('api_provider');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_gateway')) {
                $table->string('payment_gateway')->nullable()->after('payment_type');
            }

            if (! Schema::hasColumn('orders', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('payment_gateway');
            }

            if (! Schema::hasColumn('orders', 'payment_channel_label')) {
                $table->string('payment_channel_label')->nullable()->after('payment_channel');
            }

            if (! Schema::hasColumn('orders', 'midtrans_payment_type')) {
                $table->string('midtrans_payment_type')->nullable()->after('payment_channel_label');
            }

            if (! Schema::hasColumn('orders', 'midtrans_transaction_id')) {
                $table->string('midtrans_transaction_id')->nullable()->after('midtrans_payment_type');
            }

            if (! Schema::hasColumn('orders', 'midtrans_bank')) {
                $table->string('midtrans_bank')->nullable()->after('midtrans_transaction_id');
            }

            if (! Schema::hasColumn('orders', 'midtrans_va_number')) {
                $table->string('midtrans_va_number')->nullable()->after('midtrans_bank');
            }
        });

        $sort = 10;

        foreach (MidtransPaymentChannel::options() as $code => $meta) {
            $slug = 'midtrans-' . Str::slug($code);

            DB::table('payment_methods')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $meta['label'],
                    'type' => 'api',
                    'api_provider' => 'midtrans',
                    'api_endpoint' => null,
                    'description' => $meta['description'],
                    'midtrans_enabled_payments' => json_encode([$code]),
                    'instructions' => 'Pembayaran diproses otomatis melalui Midtrans. Setelah pembayaran berhasil, status order akan diperbarui otomatis.',
                    'is_active' => true,
                    'sort_order' => $sort,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $sort += 10;
        }

        DB::table('payment_methods')
            ->where('slug', 'midtrans')
            ->where('type', 'api')
            ->where('api_provider', 'midtrans')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('payment_methods')
            ->whereIn('slug', collect(array_keys(MidtransPaymentChannel::options()))
                ->map(fn ($code) => 'midtrans-' . Str::slug($code))
                ->all())
            ->delete();

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'midtrans_va_number',
                'midtrans_bank',
                'midtrans_transaction_id',
                'midtrans_payment_type',
                'payment_channel_label',
                'payment_channel',
                'payment_gateway',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            foreach ([
                'midtrans_enabled_payments',
                'description',
            ] as $column) {
                if (Schema::hasColumn('payment_methods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
