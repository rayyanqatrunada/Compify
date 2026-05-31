<?php

namespace App\Models;

use App\Services\ProductPricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFlashSaleItem extends Model
{
    protected $fillable = [
        'event_flash_sale_group_id',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(EventFlashSaleGroup::class, 'event_flash_sale_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where($this->getTable() . '.is_active', true);
    }

    public function getBasePriceAttribute(): int
    {
        return (int) ($this->product?->base_final_price ?? 0);
    }

    public function getEventPriceAttribute(): int
    {
        return app(ProductPricingService::class)->discountedPrice(
            basePrice: $this->base_price,
            discountType: $this->discount_type,
            discountValue: $this->discount_value,
        );
    }

    public function getDiscountPercentAttribute(): int
    {
        if ($this->base_price <= 0 || $this->event_price >= $this->base_price) {
            return 0;
        }

        return (int) round((($this->base_price - $this->event_price) / $this->base_price) * 100);
    }

    public function getFormattedBasePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->base_price, 0, ',', '.');
    }

    public function getFormattedEventPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->event_price, 0, ',', '.');
    }
}