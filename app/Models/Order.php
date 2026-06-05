<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'shipping_method_id',
        'payment_method_id',

        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',

        'shipping_address',
        'shipping_province',
        'shipping_city',
        'shipping_district',
        'shipping_postal_code',

        'subtotal',
        'shipping_cost',
        'discount_amount',
        'total_amount',

        'payment_status',
        'order_status',
        'payment_type',
        'payment_reference',
        'payment_redirect_url',

        'universal_discount_eligible_subtotal',
        'universal_discount_amount',
        'universal_discount_percent',
        'universal_discount_label',
        'universal_discount_campaign_key',
        'universal_discount_snapshot',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        
        'universal_discount_eligible_subtotal' => 'decimal:2',
        'universal_discount_amount' => 'decimal:2',
        'universal_discount_percent' => 'decimal:2',
        'universal_discount_snapshot' => 'array',
        
        'total_amount' => 'decimal:2',
    ];

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'CPF-' . now()->format('Ymd-His') . '-' . random_int(1000, 9999);
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->total_amount;
    }

    public function getStatusAttribute(): string
    {
        return $this->order_status;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
