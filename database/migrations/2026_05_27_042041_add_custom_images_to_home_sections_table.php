<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('home_sections', 'image')) {
                $table->string('image')->nullable()->after('description');
            }

            if (! Schema::hasColumn('home_sections', 'image_2')) {
                $table->string('image_2')->nullable()->after('image');
            }

            if (! Schema::hasColumn('home_sections', 'image_3')) {
                $table->string('image_3')->nullable()->after('image_2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn(['image', 'image_2', 'image_3']);
        });
    }
};