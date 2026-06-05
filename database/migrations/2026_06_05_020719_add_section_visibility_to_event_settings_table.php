<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->boolean('show_hero_section')->default(true)->after('is_active');
            $table->boolean('show_flash_sale_section')->default(true)->after('show_hero_section');
            $table->boolean('show_full_banner_section')->default(true)->after('show_flash_sale_section');
            $table->boolean('show_combo_package_section')->default(true)->after('show_full_banner_section');
        });
    }

    public function down(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_hero_section',
                'show_flash_sale_section',
                'show_full_banner_section',
                'show_combo_package_section',
            ]);
        });
    }
};