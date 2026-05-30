<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sale_starts_at')) {
                $table->dateTime('sale_starts_at')->nullable()->after('sale_price');
            }

            if (! Schema::hasColumn('products', 'sale_ends_at')) {
                $table->dateTime('sale_ends_at')->nullable()->after('sale_starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sale_starts_at')) {
                $table->dropColumn('sale_starts_at');
            }

            if (Schema::hasColumn('products', 'sale_ends_at')) {
                $table->dropColumn('sale_ends_at');
            }
        });
    }
};