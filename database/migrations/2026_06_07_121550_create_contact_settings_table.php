<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_settings', function (Blueprint $table) {
            $table->id();

            // Hero section
            $table->string('heading')->default('Contact Us');
            $table->string('subheading')->nullable();
            $table->text('description')->nullable();

            // Info kontak
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_country')->nullable()->default('Indonesia');

            // Jam operasional
            $table->string('open_hours')->nullable();

            // Notifikasi pesan masuk
            $table->string('notify_email')->nullable();
            $table->string('notify_phone')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_settings');
    }
};
