<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RajaOngkirShippingService
{
    public function __construct(
        private readonly ShippingApiSettingService $settings,
    ) {}

    public function searchDomesticDestination(string $keyword, int $limit = 10, int $offset = 0): array
    {
        $keyword = trim($keyword);

        if (mb_strlen($keyword) < 3) {
            return [];
        }

        $cacheKey = 'rajaongkir:domestic-destination:' . md5(strtolower($keyword) . "|{$limit}|{$offset}");
        $ttl = $this->settings->cacheMinutes();

        $callback = function () use ($keyword, $limit, $offset) {
            $response = Http::withHeaders([
                    'key' => $this->apiKey(),
                    'Accept' => 'application/json',
                ])
                ->timeout(12)
                ->retry(1, 300)
                ->get($this->endpoint('destination/domestic-destination'), [
                    'search' => $keyword,
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'RajaOngkir search destination gagal: HTTP ' . $response->status() . ' - ' . mb_substr($response->body(), 0, 180)
                );
            }

            $payload = $response->json();

            return collect(data_get($payload, 'data', []))
                ->map(fn (array $item) => [
                    'id' => (string) ($item['id'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'province_name' => (string) ($item['province_name'] ?? ''),
                    'city_name' => (string) ($item['city_name'] ?? ''),
                    'district_name' => (string) ($item['district_name'] ?? ''),
                    'subdistrict_name' => (string) ($item['subdistrict_name'] ?? ''),
                    'zip_code' => (string) ($item['zip_code'] ?? ''),
                ])
                ->filter(fn (array $item) => $item['id'] !== '' && $item['label'] !== '')
                ->values()
                ->all();
        };

        if ($ttl <= 0) {
            return $callback();
        }

        return Cache::remember($cacheKey, now()->addMinutes($ttl), $callback);
    }

    public function calculateDomesticCost(
        string|int $origin,
        string|int $destination,
        int $weightGram,
        string $courier,
        string $price = 'lowest'
    ): array {
        $response = Http::asForm()
            ->withHeaders([
                'key' => $this->apiKey(),
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->retry(1, 300)
            ->post($this->endpoint('calculate/domestic-cost'), [
                'origin' => $origin,
                'destination' => $destination,
                'weight' => max(1, $weightGram),
                'courier' => strtolower(trim($courier)),
                'price' => $price,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'RajaOngkir calculate domestic cost gagal: HTTP ' . $response->status() . ' - ' . mb_substr($response->body(), 0, 180)
            );
        }

        $payload = $response->json();

        return collect(data_get($payload, 'data', []))
            ->map(fn (array $item) => [
                'name' => (string) ($item['name'] ?? ''),
                'code' => (string) ($item['code'] ?? ''),
                'service' => (string) ($item['service'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
                'cost' => (int) ($item['cost'] ?? 0),
                'etd' => (string) ($item['etd'] ?? ''),
            ])
            ->filter(fn (array $item) => $item['code'] !== '' && $item['service'] !== '' && $item['cost'] >= 0)
            ->values()
            ->all();
    }

    private function apiKey(): string
    {
        $apiKey = (string) $this->settings->apiKey();

        if ($apiKey === '') {
            throw new RuntimeException('API key RajaOngkir belum diisi.');
        }

        return $apiKey;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('shipping_api.providers.rajaongkir.base_url', 'https://rajaongkir.komerce.id/api/v1/'), '/')
            . '/'
            . ltrim($path, '/');
    }
}
