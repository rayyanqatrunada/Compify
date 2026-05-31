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
        'discount_type',
        'discount_value',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboPackageItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
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

    public function getDiscountAmountAttribute(): int
    {
        $originalTotal = $this->original_total;

        if ($originalTotal <= 0) {
            return 0;
        }

        if ($this->discount_type === 'amount') {
            return min($originalTotal, (int) $this->discount_value);
        }

        $percent = min(100, max(0, (float) $this->discount_value));

        return (int) round($originalTotal * ($percent / 100));
    }

    public function getCalculatedPackagePriceAttribute(): int
    {
        return max(0, $this->original_total - $this->discount_amount);
    }

    public function getSavingsAttribute(): int
    {
        return $this->discount_amount;
    }

    public function getFormattedOriginalTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->original_total, 0, ',', '.');
    }

    public function getFormattedPackagePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->calculated_package_price, 0, ',', '.');
    }

    public function getFormattedSavingsAttribute(): string
    {
        return 'Rp ' . number_format($this->savings, 0, ',', '.');
    }

    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === 'amount') {
            return 'Potongan Rp ' . number_format((float) $this->discount_value, 0, ',', '.');
        }

        return 'Diskon ' . number_format((float) $this->discount_value, 0) . '%';
    }
}