<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'base_cost',
        'same_district_cost',
        'same_city_cost',
        'same_province_cost',
        'outside_province_cost',
        'free_shipping_min',
        'estimate',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'base_cost' => 'integer',
        'same_district_cost' => 'integer',
        'same_city_cost' => 'integer',
        'same_province_cost' => 'integer',
        'outside_province_cost' => 'integer',
        'free_shipping_min' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}