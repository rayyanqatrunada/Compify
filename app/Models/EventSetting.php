<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'show_hero_section' => 'boolean',
        'show_flash_sale_section' => 'boolean',
        'show_full_banner_section' => 'boolean',
        'show_combo_package_section' => 'boolean',
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
}