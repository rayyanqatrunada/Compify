<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable()->after('avatar');
            }

            if (! Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('gender');
            }
        });
    }

    public function down(): void
    {
        $columns = array_filter(
            ['gender', 'birth_date'],
            fn ($column) => Schema::hasColumn('users', $column)
        );

        if (! empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
