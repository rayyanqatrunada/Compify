<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComboPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'description',
        'package_price',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboPackageItem::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOriginalTotalAttribute(): int
    {
        return (int) $this->items->sum(function (ComboPackageItem $item) {
            return ($item->product?->final_price ?? 0) * $item->quantity;
        });
    }

    public function getSavingsAttribute(): int
    {
        return max(0, $this->original_total - (int) $this->package_price);
    }

    public function getFormattedOriginalTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->original_total, 0, ',', '.');
    }

    public function getFormattedPackagePriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->package_price, 0, ',', '.');
    }

    public function getFormattedSavingsAttribute(): string
    {
        return 'Rp ' . number_format($this->savings, 0, ',', '.');
    }
}