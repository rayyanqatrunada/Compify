<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_flash_sale_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $defaultGroupId = DB::table('event_flash_sale_groups')->insertGetId([
            'name' => 'Flash Sale Utama',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('event_flash_sale_items', function (Blueprint $table) {
            $table->foreignId('event_flash_sale_group_id')
                ->nullable()
                ->after('id')
                ->constrained('event_flash_sale_groups')
                ->nullOnDelete();
        });

        DB::table('event_flash_sale_items')
            ->whereNull('event_flash_sale_group_id')
            ->update([
                'event_flash_sale_group_id' => $defaultGroupId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('event_flash_sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_flash_sale_group_id');
        });

        Schema::dropIfExists('event_flash_sale_groups');
    }
};