<?php

namespace App\Services\Shipping;

use App\Models\ShippingSetting;

class ShippingApiSettingService
{
    public function setting(): ShippingSetting
    {
        return ShippingSetting::firstOrCreate(
            ['id' => 1],
            [
                'country' => 'Indonesia',
                'province' => 'Jawa Tengah',
                'city' => 'Jepara',
                'district' => 'Bangsri',
                'shipping_api_provider' => config('shipping_api.default_provider', 'manual'),
                'shipping_api_enabled' => (bool) config('shipping_api.enabled', false),
                'shipping_api_fallback_manual' => (bool) config('shipping_api.fallback_manual', true),
                'shipping_api_default_weight_gram' => 1000,
                'shipping_api_cache_minutes' => (int) config('shipping_api.cache_minutes', 30),
            ]
        );
    }

    public function provider(): string
    {
        return $this->setting()->shipping_api_provider ?: config('shipping_api.default_provider', 'manual');
    }

    public function apiKey(): ?string
    {
        $provider = $this->provider();
        $setting = $this->setting();

        return $setting->shipping_api_key
            ?: config("shipping_api.providers.{$provider}.api_key");
    }

    public function isEnabled(): bool
    {
        $setting = $this->setting();

        return (bool) $setting->shipping_api_enabled
            && $this->provider() !== 'manual';
    }

    public function isReady(): bool
    {
        $setting = $this->setting();

        return $this->isEnabled()
            && filled($this->apiKey())
            && filled($setting->shipping_api_origin_area_id);
    }

    public function courierCodes(): array
    {
        $setting = $this->setting();
        $codes = $setting->courierCodes();

        if ($codes !== []) {
            return $codes;
        }

        return config('shipping_api.default_couriers', ['jne', 'jnt', 'sicepat', 'anteraja', 'pos']);
    }

    public function origin(): array
    {
        $setting = $this->setting();

        return [
            'area_id' => $setting->shipping_api_origin_area_id,
            'label' => $setting->shipping_api_origin_label,
            'country' => $setting->country,
            'province' => $setting->province,
            'city' => $setting->city,
            'district' => $setting->district,
            'postal_code' => $setting->postal_code,
        ];
    }

    public function fallbackManualEnabled(): bool
    {
        return (bool) $this->setting()->shipping_api_fallback_manual;
    }

    public function cacheMinutes(): int
    {
        return max(0, (int) $this->setting()->shipping_api_cache_minutes);
    }

    public function defaultWeightGram(): int
    {
        return max(1, (int) ($this->setting()->shipping_api_default_weight_gram ?: 1000));
    }
}
