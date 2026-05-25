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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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
     * Harga final produk.
     * Kalau ada sale_price, pakai sale_price.
     * Kalau tidak ada, pakai price normal.
     */
    public function getFinalPriceAttribute(): float
    {
        return $this->sale_price && $this->sale_price > 0
            ? (float) $this->sale_price
            : (float) $this->price;
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
}