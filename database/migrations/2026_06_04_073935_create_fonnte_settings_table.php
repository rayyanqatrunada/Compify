<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonnte_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_active')->default(false);

            $table->string('api_url')->default('https://api.fonnte.com/send');
            $table->text('token')->nullable();

            $table->string('admin_phone')->nullable();

            $table->boolean('send_customer_order_created')->default(true);
            $table->boolean('send_admin_order_created')->default(true);

            $table->text('customer_order_created_template')->nullable();
            $table->text('admin_order_created_template')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonnte_settings');
    }
};