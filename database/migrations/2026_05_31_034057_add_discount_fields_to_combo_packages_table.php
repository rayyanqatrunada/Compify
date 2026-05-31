<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combo_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('combo_packages', 'discount_type')) {
                $table->string('discount_type')->default('percent')->after('package_price');
            }

            if (! Schema::hasColumn('combo_packages', 'discount_value')) {
                $table->decimal('discount_value', 14, 2)->default(0)->after('discount_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('combo_packages', function (Blueprint $table) {
            if (Schema::hasColumn('combo_packages', 'discount_value')) {
                $table->dropColumn('discount_value');
            }

            if (Schema::hasColumn('combo_packages', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });
    }
};