<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_rate_source')) {
                $table->string('shipping_rate_source')->nullable()->after('shipping_destination_label');
            }

            if (! Schema::hasColumn('orders', 'shipping_courier_code')) {
                $table->string('shipping_courier_code')->nullable()->after('shipping_rate_source');
            }

            if (! Schema::hasColumn('orders', 'shipping_courier_name')) {
                $table->string('shipping_courier_name')->nullable()->after('shipping_courier_code');
            }

            if (! Schema::hasColumn('orders', 'shipping_service_code')) {
                $table->string('shipping_service_code')->nullable()->after('shipping_courier_name');
            }

            if (! Schema::hasColumn('orders', 'shipping_service_name')) {
                $table->string('shipping_service_name')->nullable()->after('shipping_service_code');
            }

            if (! Schema::hasColumn('orders', 'shipping_estimate')) {
                $table->string('shipping_estimate')->nullable()->after('shipping_service_name');
            }

            if (! Schema::hasColumn('orders', 'shipping_rate_payload')) {
                $table->json('shipping_rate_payload')->nullable()->after('shipping_estimate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'shipping_rate_payload',
                'shipping_estimate',
                'shipping_service_name',
                'shipping_service_code',
                'shipping_courier_name',
                'shipping_courier_code',
                'shipping_rate_source',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
