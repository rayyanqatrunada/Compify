<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'payment_redirect_url')) {
            DB::statement('ALTER TABLE orders MODIFY payment_redirect_url LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'payment_redirect_url')) {
            DB::statement('ALTER TABLE orders MODIFY payment_redirect_url VARCHAR(255) NULL');
        }
    }
};