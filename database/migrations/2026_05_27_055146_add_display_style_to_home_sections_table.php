<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('home_sections', 'display_style')) {
                $table->string('display_style')->nullable()->after('section_type');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('home_sections', 'display_style')) {
            Schema::table('home_sections', function (Blueprint $table) {
                $table->dropColumn('display_style');
            });
        }
    }
};