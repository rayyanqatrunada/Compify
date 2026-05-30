<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->after('user_id')->constrained('shipping_methods')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('shipping_method_id')->constrained('payment_methods')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'shipping_cost')) {
                $table->unsignedBigInteger('shipping_cost')->default(0);
            }

            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending');
            }

            if (! Schema::hasColumn('orders', 'shipping_province')) {
                $table->string('shipping_province')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_city')) {
                $table->string('shipping_city')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_district')) {
                $table->string('shipping_district')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_address')) {
                $table->text('shipping_address')->nullable();
            }

            if (! Schema::hasColumn('orders', 'shipping_postal_code')) {
                $table->string('shipping_postal_code')->nullable();
            }

            if (! Schema::hasColumn('orders', 'phone')) {
                $table->string('phone')->nullable();
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