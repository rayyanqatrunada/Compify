<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('banners', 'slug')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banners', 'slug')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};