<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonnte_message_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_type')->nullable();
            $table->string('target')->nullable();
            $table->string('status')->default('pending');

            $table->longText('message')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonnte_message_logs');
    }
};