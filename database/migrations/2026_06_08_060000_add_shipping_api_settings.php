<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('shipping_settings', 'shipping_api_provider')) {
                $table->string('shipping_api_provider')->default('manual')->after('postal_code');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_enabled')) {
                $table->boolean('shipping_api_enabled')->default(false)->after('shipping_api_provider');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_key')) {
                $table->text('shipping_api_key')->nullable()->after('shipping_api_enabled');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_origin_area_id')) {
                $table->string('shipping_api_origin_area_id')->nullable()->after('shipping_api_key');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_origin_label')) {
                $table->text('shipping_api_origin_label')->nullable()->after('shipping_api_origin_area_id');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_couriers')) {
                $table->string('shipping_api_couriers')->nullable()->after('shipping_api_origin_label');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_default_weight_gram')) {
                $table->unsignedInteger('shipping_api_default_weight_gram')->default(1000)->after('shipping_api_couriers');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_cache_minutes')) {
                $table->unsignedInteger('shipping_api_cache_minutes')->default(30)->after('shipping_api_default_weight_gram');
            }

            if (! Schema::hasColumn('shipping_settings', 'shipping_api_fallback_manual')) {
                $table->boolean('shipping_api_fallback_manual')->default(true)->after('shipping_api_cache_minutes');
            }
        });

        DB::table('shipping_settings')->updateOrInsert(
            ['id' => 1],
            [
                'country' => 'Indonesia',
                'province' => 'Jawa Tengah',
                'city' => 'Jepara',
                'district' => 'Bangsri',
                'shipping_api_provider' => 'manual',
                'shipping_api_enabled' => false,
                'shipping_api_couriers' => 'jne,jnt,sicepat,anteraja,pos',
                'shipping_api_default_weight_gram' => 1000,
                'shipping_api_cache_minutes' => 30,
                'shipping_api_fallback_manual' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table) {
            foreach ([
                'shipping_api_fallback_manual',
                'shipping_api_cache_minutes',
                'shipping_api_default_weight_gram',
                'shipping_api_couriers',
                'shipping_api_origin_label',
                'shipping_api_origin_area_id',
                'shipping_api_key',
                'shipping_api_enabled',
                'shipping_api_provider',
            ] as $column) {
                if (Schema::hasColumn('shipping_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
