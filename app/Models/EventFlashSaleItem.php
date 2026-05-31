<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFlashSaleItem extends Model
{
    protected $fillable = [
        'product_id',
        'discount_type',
        'discount_value',
        'stock_limit',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'stock_limit' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getBasePriceAttribute(): int
    {
        return (int) ($this->product?->final_price ?? 0);
    }

    public function getEventPriceAttribute(): int
    {
        $base = $this->base_price;

        if ($base <= 0) {
            return 0;
        }

        if ($this->discount_type === 'amount') {
            return max(0, $base - (int) $this->discount_value);
        }

        $discount = $base * ((float) $this->discount_value / 100);

        return max(0, (int) round($base - $discount));
    }

    public function getDiscountPercentAttribute(): int
    {
        if ($this->base_price <= 0 || $this->event_price >= $this->base_price) {
            return 0;
        }

        return (int) round((($this->base_price - $this->event_price) / $this->base_price) * 100);
    }

    public function getFormattedEventPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->event_price, 0, ',', '.');
    }
}