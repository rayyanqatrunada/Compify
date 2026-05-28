<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // manual, url, qr, api
            $table->string('type')->default('manual');

            $table->string('logo')->nullable();
            $table->string('qr_image')->nullable();

            $table->string('payment_url')->nullable();
            $table->string('api_provider')->nullable();
            $table->string('api_endpoint')->nullable();

            $table->text('instructions')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};