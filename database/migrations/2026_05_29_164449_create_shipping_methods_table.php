<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            $table->unsignedBigInteger('base_cost')->default(0);
            $table->unsignedBigInteger('same_district_cost')->nullable();
            $table->unsignedBigInteger('same_city_cost')->nullable();
            $table->unsignedBigInteger('same_province_cost')->nullable();
            $table->unsignedBigInteger('outside_province_cost')->nullable();

            $table->unsignedBigInteger('free_shipping_min')->nullable();

            $table->string('estimate')->nullable(); // contoh: 1-3 hari
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};