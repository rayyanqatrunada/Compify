<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_layout_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('home_layout_group_id')
                ->constrained('home_layout_groups')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('slot_number');

            // none, product_display, full_banner, split_banner, gallery
            $table->string('slot_type')->default('none');

            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('home_section_id')->nullable()->constrained('home_sections')->nullOnDelete();

            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['home_layout_group_id', 'slot_number'], 'home_layout_group_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_layout_slots');
    }
};