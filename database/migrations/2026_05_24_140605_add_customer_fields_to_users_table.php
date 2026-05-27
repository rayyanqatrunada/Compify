<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address');
            }

            if (! Schema::hasColumn('users', 'province')) {
                $table->string('province')->nullable()->after('city');
            }

            if (! Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('province');
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('postal_code');
            }
        });
    }

    public function down(): void
    {
        $columns = array_filter(
            ['username', 'phone', 'address', 'city', 'province', 'postal_code', 'avatar'],
            fn ($column) => Schema::hasColumn('users', $column)
        );

        if (! empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};