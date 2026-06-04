<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_category_grid_settings', function (Blueprint $table) {
            $table->id();

            $table->string('title')->default('Kategori Pilihan');
            $table->string('subtitle')->nullable();

            $table->unsignedTinyInteger('columns_desktop')->default(6);
            $table->unsignedTinyInteger('columns_tablet')->default(4);
            $table->unsignedTinyInteger('columns_mobile')->default(2);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_category_grid_settings');
    }
};