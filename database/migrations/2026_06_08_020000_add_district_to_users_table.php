<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'district')) {
                $table->string('district')->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'district')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }
    }
};
