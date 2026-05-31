<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboPackageItem extends Model
{
    protected $fillable = [
        'combo_package_id',
        'product_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function comboPackage(): BelongsTo
    {
        return $this->belongsTo(ComboPackage::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLineTotalAttribute(): int
    {
        return (int) (($this->product?->final_price ?? 0) * $this->quantity);
    }
}