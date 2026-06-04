<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_layout_slots', function (Blueprint $table) {
            $table->string('product_source')
                ->default('category')
                ->after('slot_type');
        });
    }

    public function down(): void
    {
        Schema::table('home_layout_slots', function (Blueprint $table) {
            $table->dropColumn('product_source');
        });
    }
};