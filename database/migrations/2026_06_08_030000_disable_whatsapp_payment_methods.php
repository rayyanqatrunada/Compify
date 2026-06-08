<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        DB::table('payment_methods')
            ->where('type', 'whatsapp')
            ->update([
                'is_active' => false,
                'auto_redirect' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        DB::table('payment_methods')
            ->where('type', 'whatsapp')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
