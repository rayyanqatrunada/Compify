<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $fillable = [
        'country',
        'province',
        'city',
        'district',
        'postal_code',

        'shipping_api_provider',
        'shipping_api_enabled',
        'shipping_api_key',
        'shipping_api_origin_area_id',
        'shipping_api_origin_label',
        'shipping_api_couriers',
        'shipping_api_default_weight_gram',
        'shipping_api_cache_minutes',
        'shipping_api_fallback_manual',
    ];

    protected $casts = [
        'shipping_api_enabled' => 'boolean',
        'shipping_api_fallback_manual' => 'boolean',
        'shipping_api_default_weight_gram' => 'integer',
        'shipping_api_cache_minutes' => 'integer',
        'shipping_api_key' => 'encrypted',
    ];

    public function courierCodes(): array
    {
        return collect(explode(',', (string) $this->shipping_api_couriers))
            ->map(fn ($code) => strtolower(trim($code)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function apiProviderLabel(): string
    {
        return match ($this->shipping_api_provider) {
            'rajaongkir' => 'RajaOngkir',
            'biteship' => 'Biteship',
            default => 'Manual',
        };
    }
}
