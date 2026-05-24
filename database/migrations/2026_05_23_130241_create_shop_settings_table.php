<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();

            $table->string('site_name')->default('Compify');
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();

            $table->string('login_heading')->default('Admin Sign In');
            $table->string('login_subheading')->default('Masuk ke dashboard Compify');
            $table->string('login_showcase_title')->default('Manage your store beautifully');
            $table->text('login_showcase_text')->nullable();
            $table->string('login_image')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};