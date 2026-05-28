<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_method_id')) {
                $table->foreignId('payment_method_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('payment_methods')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('status');
            }

            if (! Schema::hasColumn('orders', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_type');
            }

            if (! Schema::hasColumn('orders', 'payment_redirect_url')) {
                $table->string('payment_redirect_url')->nullable()->after('payment_reference');
            }

            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }

            if (! Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email')->nullable();
            }

            if (! Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_address')) {
                $table->text('shipping_address')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_city')) {
                $table->string('shipping_city')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_province')) {
                $table->string('shipping_province')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_postal_code')) {
                $table->string('shipping_postal_code')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};