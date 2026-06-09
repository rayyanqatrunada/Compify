<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'weight_gram')) {
                $table->unsignedInteger('weight_gram')->default(1000)->after('stock');
            }

            if (! Schema::hasColumn('products', 'length_cm')) {
                $table->unsignedSmallInteger('length_cm')->nullable()->after('weight_gram');
            }

            if (! Schema::hasColumn('products', 'width_cm')) {
                $table->unsignedSmallInteger('width_cm')->nullable()->after('length_cm');
            }

            if (! Schema::hasColumn('products', 'height_cm')) {
                $table->unsignedSmallInteger('height_cm')->nullable()->after('width_cm');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'total_weight_gram')) {
                $table->unsignedInteger('total_weight_gram')->default(0)->after('shipping_cost');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'weight_gram')) {
                $table->unsignedInteger('weight_gram')->default(0)->after('quantity');
            }

            if (! Schema::hasColumn('order_items', 'line_weight_gram')) {
                $table->unsignedInteger('line_weight_gram')->default(0)->after('weight_gram');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            foreach (['line_weight_gram', 'weight_gram'] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'total_weight_gram')) {
                $table->dropColumn('total_weight_gram');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (['height_cm', 'width_cm', 'length_cm', 'weight_gram'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
