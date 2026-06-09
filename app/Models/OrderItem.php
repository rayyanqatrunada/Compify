<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_type',
        'product_id',
        'combo_package_id',
        'event_flash_sale_item_id',

        'product_name',
        'product_slug',
        'product_image',

        'price',
        'original_price',
        'discount_amount',
        'price_label',

        'quantity',
        'weight_gram',
        'line_weight_gram',
        'total',
        'snapshot_data',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'weight_gram' => 'integer',
        'line_weight_gram' => 'integer',
        'snapshot_data' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function comboPackage(): BelongsTo
    {
        return $this->belongsTo(ComboPackage::class);
    }

    public function flashSaleItem(): BelongsTo
    {
        return $this->belongsTo(EventFlashSaleItem::class, 'event_flash_sale_item_id');
    }

    public function getIsComboPackageAttribute(): bool
    {
        return $this->item_type === 'combo_package';
    }

    public function getIsFlashSaleAttribute(): bool
    {
        return $this->item_type === 'event_flash_sale';
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
    }

    public function getFormattedOriginalPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->original_price, 0, ',', '.');
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->discount_amount, 0, ',', '.');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total, 0, ',', '.');
    }

    public function getFormattedLineWeightAttribute(): string
    {
        $weight = max(0, (int) $this->line_weight_gram);

        if ($weight >= 1000) {
            return number_format($weight / 1000, $weight % 1000 === 0 ? 0 : 2, ',', '.') . ' kg';
        }

        return number_format($weight, 0, ',', '.') . ' g';
    }

}
