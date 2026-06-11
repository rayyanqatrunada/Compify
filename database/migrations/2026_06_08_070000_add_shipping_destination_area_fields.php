<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_destination_area_id')) {
                $table->string('shipping_destination_area_id')->nullable()->after('shipping_postal_code');
            }

            if (! Schema::hasColumn('orders', 'shipping_destination_label')) {
                $table->text('shipping_destination_label')->nullable()->after('shipping_destination_area_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'shipping_destination_area_id')) {
                $table->string('shipping_destination_area_id')->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('users', 'shipping_destination_label')) {
                $table->text('shipping_destination_label')->nullable()->after('shipping_destination_area_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'shipping_destination_label',
                'shipping_destination_area_id',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'shipping_destination_label',
                'shipping_destination_area_id',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
