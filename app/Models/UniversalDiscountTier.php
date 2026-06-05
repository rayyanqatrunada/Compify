<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversalDiscountTier extends Model
{
    protected $fillable = [
        'event_setting_id',
        'min_purchase',
        'discount_percent',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_purchase' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function eventSetting(): BelongsTo
    {
        return $this->belongsTo(EventSetting::class);
    }

    public function getFormattedMinPurchaseAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->min_purchase, 0, ',', '.');
    }

    public function getDiscountLabelAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->discount_percent, 2, ',', '.'), '0'), ',') . '%';
    }
}