<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'item_type')) {
                $table->string('item_type')->default('product')->after('order_id');
            }

            if (! Schema::hasColumn('order_items', 'combo_package_id')) {
                $table->foreignId('combo_package_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('combo_packages')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('order_items', 'event_flash_sale_item_id')) {
                $table->foreignId('event_flash_sale_item_id')
                    ->nullable()
                    ->after('combo_package_id')
                    ->constrained('event_flash_sale_items')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('order_items', 'product_slug')) {
                $table->string('product_slug')->nullable()->after('product_name');
            }

            if (! Schema::hasColumn('order_items', 'product_image')) {
                $table->string('product_image')->nullable()->after('product_slug');
            }

            if (! Schema::hasColumn('order_items', 'original_price')) {
                $table->decimal('original_price', 12, 2)->default(0)->after('price');
            }

            if (! Schema::hasColumn('order_items', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('original_price');
            }

            if (! Schema::hasColumn('order_items', 'price_label')) {
                $table->string('price_label')->nullable()->after('discount_amount');
            }

            if (! Schema::hasColumn('order_items', 'snapshot_data')) {
                $table->json('snapshot_data')->nullable()->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'event_flash_sale_item_id')) {
                $table->dropConstrainedForeignId('event_flash_sale_item_id');
            }

            if (Schema::hasColumn('order_items', 'combo_package_id')) {
                $table->dropConstrainedForeignId('combo_package_id');
            }

            foreach ([
                'item_type',
                'product_slug',
                'product_image',
                'original_price',
                'discount_amount',
                'price_label',
                'snapshot_data',
            ] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};