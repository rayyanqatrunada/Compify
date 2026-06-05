<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSetting extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'is_active',
        'starts_at',
        'ends_at',

        'show_hero_section',
        'show_flash_sale_section',
        'show_full_banner_section',
        'show_combo_package_section',

        'universal_discount_mode',
        'universal_discount_scope',
        'universal_discount_starts_at',
        'universal_discount_ends_at',
        'universal_discount_batch',
        'universal_discount_campaign_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',

        'show_hero_section' => 'boolean',
        'show_flash_sale_section' => 'boolean',
        'show_full_banner_section' => 'boolean',
        'show_combo_package_section' => 'boolean',

        'universal_discount_starts_at' => 'datetime',
        'universal_discount_ends_at' => 'datetime',
        'universal_discount_batch' => 'integer',
    ];

    public static function current(): ?self
    {
        return self::query()->first();
    }

    public static function activeNow(): ?self
    {
        $event = self::current();

        return $event?->is_running ? $event : null;
    }

    public function universalDiscountTiers(): HasMany
    {
        return $this->hasMany(UniversalDiscountTier::class)
            ->orderBy('sort_order')
            ->orderBy('min_purchase');
    }

    public function activeUniversalDiscountTiers(): HasMany
    {
        return $this->universalDiscountTiers()
            ->where('is_active', true);
    }

    public function getIsRunningAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function showHeroSection(): bool
    {
        return (bool) $this->show_hero_section;
    }

    public function showFlashSaleSection(): bool
    {
        return (bool) $this->show_flash_sale_section;
    }

    public function showFullBannerSection(): bool
    {
        return (bool) $this->show_full_banner_section;
    }

    public function showComboPackageSection(): bool
    {
        return (bool) $this->show_combo_package_section;
    }

    public function getUniversalDiscountCampaignKeyValueAttribute(): string
    {
        return $this->universal_discount_campaign_key
            ?: 'universal-discount-batch-' . max(1, (int) $this->universal_discount_batch);
    }

    public function getIsUniversalDiscountPeriodActiveAttribute(): bool
    {
        $now = now();

        if ($this->universal_discount_starts_at && $now->lt($this->universal_discount_starts_at)) {
            return false;
        }

        if ($this->universal_discount_ends_at && $now->gt($this->universal_discount_ends_at)) {
            return false;
        }

        return true;
    }

    public function getIsUniversalDiscountActiveAttribute(): bool
    {
        if ($this->universal_discount_mode === 'off') {
            return false;
        }

        if (! $this->is_universal_discount_period_active) {
            return false;
        }

        if ($this->universal_discount_mode === 'event_only') {
            return $this->is_running;
        }

        if ($this->universal_discount_mode === 'always') {
            return true;
        }

        return false;
    }
}