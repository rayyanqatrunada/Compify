<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->string('universal_discount_mode')->default('off')->after('show_combo_package_section');
            $table->string('universal_discount_scope')->default('exclude_flash_and_combo')->after('universal_discount_mode');

            $table->timestamp('universal_discount_starts_at')->nullable()->after('universal_discount_scope');
            $table->timestamp('universal_discount_ends_at')->nullable()->after('universal_discount_starts_at');

            $table->unsignedInteger('universal_discount_batch')->default(1)->after('universal_discount_ends_at');
            $table->string('universal_discount_campaign_key')->nullable()->after('universal_discount_batch');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('universal_discount_eligible_subtotal', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('universal_discount_amount', 12, 2)->default(0)->after('universal_discount_eligible_subtotal');
            $table->decimal('universal_discount_percent', 8, 2)->default(0)->after('universal_discount_amount');

            $table->string('universal_discount_label')->nullable()->after('universal_discount_percent');
            $table->string('universal_discount_campaign_key')->nullable()->after('universal_discount_label');
            $table->json('universal_discount_snapshot')->nullable()->after('universal_discount_campaign_key');
        });

        Schema::create('universal_discount_tiers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_setting_id')->constrained('event_settings')->cascadeOnDelete();

            $table->decimal('min_purchase', 12, 2);
            $table->decimal('discount_percent', 8, 2);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['event_setting_id', 'is_active']);
        });

        Schema::create('universal_discount_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('campaign_key');

            $table->decimal('eligible_subtotal', 12, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'campaign_key']);
            $table->index('campaign_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universal_discount_usages');
        Schema::dropIfExists('universal_discount_tiers');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'universal_discount_eligible_subtotal',
                'universal_discount_amount',
                'universal_discount_percent',
                'universal_discount_label',
                'universal_discount_campaign_key',
                'universal_discount_snapshot',
            ]);
        });

        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn([
                'universal_discount_mode',
                'universal_discount_scope',
                'universal_discount_starts_at',
                'universal_discount_ends_at',
                'universal_discount_batch',
                'universal_discount_campaign_key',
            ]);
        });
    }
};