<?php

namespace App\Models;

use App\Services\ProductPricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'sku',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'stock',
        'weight_gram',
        'length_cm',
        'width_cm',
        'height_cm',
        'image',
        'is_featured',
        'is_new',
        'is_active',
        'sort_order',
        'sale_starts_at',
        'sale_ends_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'weight_gram' => 'integer',
        'length_cm' => 'integer',
        'width_cm' => 'integer',
        'height_cm' => 'integer',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function scopeActive($query)
    {
        return $query->where($this->getTable() . '.is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeNewProduct($query)
    {
        return $query->where('is_new', true);
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getPricingAttribute(): array
    {
        return app(ProductPricingService::class)->forProduct($this);
    }

    public function getBaseFinalPriceAttribute(): int
    {
        return app(ProductPricingService::class)->regularPricing($this)['unit_price'];
    }

    public function getFinalPriceAttribute(): int
    {
        return (int) $this->pricing['unit_price'];
    }

    public function getOriginalPriceAttribute(): int
    {
        return (int) $this->pricing['original_price'];
    }

    public function getDiscountAmountAttribute(): int
    {
        return (int) $this->pricing['discount_amount'];
    }

    public function getHasDiscountAttribute(): bool
    {
        return (bool) $this->pricing['is_discounted'];
    }

    public function getIsPromoActiveAttribute(): bool
    {
        return $this->has_discount;
    }

    public function getIsEventPriceAttribute(): bool
    {
        return (bool) $this->pricing['is_event_price'];
    }

    public function getPriceSourceAttribute(): string
    {
        return (string) $this->pricing['source'];
    }

    public function getPriceSourceLabelAttribute(): string
    {
        return (string) $this->pricing['label'];
    }

    public function getEventFlashSaleItemIdAttribute(): ?int
    {
        return $this->pricing['event_flash_sale_item_id'];
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        return $this->discount_percent;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        $percent = (int) $this->pricing['discount_percent'];

        return $percent > 0 ? $percent : null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
    }

    public function getFormattedOriginalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->original_price, 0, ',', '.');
    }

    public function getFormattedBaseFinalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->base_final_price, 0, ',', '.');
    }

    public function getFormattedFinalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->final_price, 0, ',', '.');
    }

    public function getActiveEventFlashSaleItemAttribute(): ?EventFlashSaleItem
    {
        return app(ProductPricingService::class)->activeFlashSaleItemForProduct($this);
    }

    public function getShippingWeightGramAttribute(): int
    {
        return max(1, (int) ($this->weight_gram ?: 1000));
    }

    public function getFormattedWeightAttribute(): string
    {
        $weight = $this->shipping_weight_gram;

        if ($weight >= 1000) {
            return number_format($weight / 1000, $weight % 1000 === 0 ? 0 : 2, ',', '.') . ' kg';
        }

        return number_format($weight, 0, ',', '.') . ' g';
    }

    public function getDimensionsLabelAttribute(): string
    {
        if (! $this->length_cm && ! $this->width_cm && ! $this->height_cm) {
            return 'Dimensi belum diisi';
        }

        return ($this->length_cm ?: 0) . ' × ' . ($this->width_cm ?: 0) . ' × ' . ($this->height_cm ?: 0) . ' cm';
    }

}
