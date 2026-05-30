<?php

namespace App\Models;

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
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
    ];

    /**
     * Route model binding pakai slug.
     * Jadi /product/nama-produk akan otomatis mencari dari kolom slug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Relasi product ke category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi product ke brand.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Scope untuk mengambil produk aktif saja.
     * Contoh: Product::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope produk featured / unggulan.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope produk baru.
     */
    public function scopeNewProduct($query)
    {
        return $query->where('is_new', true);
    }

    /**
     * Cek apakah produk sedang diskon.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->sale_price
            && $this->sale_price > 0
            && $this->sale_price < $this->price;
    }

    /**
     * Persentase diskon.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (! $this->has_discount || $this->price <= 0) {
            return 0;
        }

        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /**
     * Cek stok tersedia.
     */
    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Format harga normal.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
    }

    /**
     * Format harga final.
     */
    public function getFormattedFinalPriceAttribute(): string
        {
            return 'Rp ' . number_format($this->final_price, 0, ',', '.');
        }

        public function getIsPromoActiveAttribute(): bool
    {
        if (! $this->sale_price || $this->price <= 0) {
            return false;
        }

        if ($this->sale_price >= $this->price) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $now->lt($this->sale_starts_at)) {
            return false;
        }

        if ($this->sale_ends_at && $now->gt($this->sale_ends_at)) {
            return false;
        }

        return true;
    }

    public function getFinalPriceAttribute(): int
    {
        return $this->is_promo_active
            ? (int) $this->sale_price
            : (int) $this->price;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->is_promo_active) {
            return null;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }
}