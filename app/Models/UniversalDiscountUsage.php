<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversalDiscountUsage extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'campaign_key',
        'eligible_subtotal',
        'discount_percent',
        'discount_amount',
        'used_at',
    ];

    protected $casts = [
        'eligible_subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}