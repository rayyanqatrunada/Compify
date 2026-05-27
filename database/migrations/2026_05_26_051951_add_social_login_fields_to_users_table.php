<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable();
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_filter(
            ['google_id', 'provider', 'avatar'],
            fn ($column) => Schema::hasColumn('users', $column)
        );

        if (! empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};