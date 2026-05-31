<?php

namespace App\Services;

use App\Models\EventFlashSaleItem;
use App\Models\EventSetting;
use App\Models\Product;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class ProductPricingService
{
    protected static bool $activeEventResolved = false;
    protected static ?EventSetting $activeEvent = null;

    protected static array $pricingCache = [];
    protected static array $flashSaleItemCache = [];

    public function forProduct(Product $product): array
    {
        if (! $product->exists) {
            return $this->regularPricing($product);
        }

        $cacheKey = $this->productCacheKey($product);

        if (isset(static::$pricingCache[$cacheKey])) {
            return static::$pricingCache[$cacheKey];
        }

        $regular = $this->regularPricing($product);

        if (! $this->activeEvent()) {
            return static::$pricingCache[$cacheKey] = $regular;
        }

        $flashSaleItem = $this->activeFlashSaleItemForProduct($product);

        if (! $flashSaleItem) {
            return static::$pricingCache[$cacheKey] = $regular;
        }

        if (! app(EventFlashSaleStockService::class)->availableForItem($flashSaleItem)) {
            return static::$pricingCache[$cacheKey] = $regular;
        }

        $eventPrice = $this->discountedPrice(
            basePrice: $regular['unit_price'],
            discountType: $flashSaleItem->discount_type,
            discountValue: $flashSaleItem->discount_value,
        );

        if ($eventPrice >= $regular['unit_price']) {
            return static::$pricingCache[$cacheKey] = $regular;
        }

        $discountAmount = max(0, $regular['unit_price'] - $eventPrice);

        $discountPercent = $regular['unit_price'] > 0
            ? (int) round(($discountAmount / $regular['unit_price']) * 100)
            : 0;

        return static::$pricingCache[$cacheKey] = [
            'source' => 'event_flash_sale',
            'label' => 'Flash Sale',
            'unit_price' => $eventPrice,
            'original_price' => $regular['unit_price'],
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'event_flash_sale_item_id' => $flashSaleItem->id,
            'is_event_price' => true,
            'is_discounted' => true,
        ];
    }

    public function regularPricing(Product $product): array
    {
        $price = (int) round((float) $product->price);

        $salePrice = $product->sale_price !== null
            ? (int) round((float) $product->sale_price)
            : null;

        $isSaleActive = false;

        if ($salePrice && $salePrice > 0 && $salePrice < $price) {
            $now = now();

            $startsOk = ! $product->sale_starts_at || $now->gte($product->sale_starts_at);
            $endsOk = ! $product->sale_ends_at || $now->lte($product->sale_ends_at);

            $isSaleActive = $startsOk && $endsOk;
        }

        $unitPrice = $isSaleActive ? $salePrice : $price;
        $discountAmount = max(0, $price - $unitPrice);

        $discountPercent = $price > 0 && $discountAmount > 0
            ? (int) round(($discountAmount / $price) * 100)
            : 0;

        return [
            'source' => $isSaleActive ? 'product_sale' : 'regular',
            'label' => $isSaleActive ? 'Promo' : 'Regular',
            'unit_price' => $unitPrice,
            'original_price' => $price,
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'event_flash_sale_item_id' => null,
            'is_event_price' => false,
            'is_discounted' => $discountAmount > 0,
        ];
    }

    public function activeFlashSaleItemForProduct(Product $product): ?EventFlashSaleItem
    {
        if (! $product->exists) {
            return null;
        }

        $productId = (int) $product->id;

        if (array_key_exists($productId, static::$flashSaleItemCache)) {
            return static::$flashSaleItemCache[$productId];
        }

        if (! $this->activeEvent()) {
            return static::$flashSaleItemCache[$productId] = null;
        }

        return static::$flashSaleItemCache[$productId] = EventFlashSaleItem::query()
            ->where('product_id', $productId)
            ->where('event_flash_sale_items.is_active', true)
            ->whereHas('group', function ($query) {
                $query->where('event_flash_sale_groups.is_active', true);
            })
            ->orderBy('event_flash_sale_items.sort_order')
            ->orderBy('event_flash_sale_items.id')
            ->first();
    }

    public function preload($products): void
    {
        $products = $this->normalizeProducts($products);

        if ($products->isEmpty()) {
            return;
        }

        $ids = $products
            ->filter(fn ($product) => $product instanceof Product && $product->exists)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        if (! $this->activeEvent()) {
            foreach ($products as $product) {
                if ($product instanceof Product && $product->exists) {
                    static::$pricingCache[$this->productCacheKey($product)] = $this->regularPricing($product);
                    static::$flashSaleItemCache[(int) $product->id] = null;
                }
            }

            return;
        }

        $missingIds = $ids
            ->filter(fn ($id) => ! array_key_exists((int) $id, static::$flashSaleItemCache))
            ->values();

        if ($missingIds->isNotEmpty()) {
            $flashSaleItems = EventFlashSaleItem::query()
                ->whereIn('product_id', $missingIds)
                ->where('event_flash_sale_items.is_active', true)
                ->whereHas('group', function ($query) {
                    $query->where('event_flash_sale_groups.is_active', true);
                })
                ->orderBy('event_flash_sale_items.sort_order')
                ->orderBy('event_flash_sale_items.id')
                ->get()
                ->unique('product_id')
                ->keyBy('product_id');

            foreach ($missingIds as $id) {
                static::$flashSaleItemCache[(int) $id] = $flashSaleItems->get((int) $id);
            }
        }

        foreach ($products as $product) {
            if ($product instanceof Product && $product->exists) {
                $this->forProduct($product);
            }
        }
    }

    public function activeEvent(): ?EventSetting
    {
        if (! static::$activeEventResolved) {
            static::$activeEvent = EventSetting::activeNow();
            static::$activeEventResolved = true;
        }

        return static::$activeEvent;
    }

    public function discountedPrice(int $basePrice, string $discountType, int|float|string|null $discountValue): int
    {
        $discountValue = (float) $discountValue;

        if ($basePrice <= 0 || $discountValue <= 0) {
            return max(0, $basePrice);
        }

        if ($discountType === 'amount') {
            return max(0, $basePrice - (int) round($discountValue));
        }

        $percent = min(100, max(0, $discountValue));
        $discountAmount = $basePrice * ($percent / 100);

        return max(0, (int) round($basePrice - $discountAmount));
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public function clearCache(): void
    {
        static::$activeEventResolved = false;
        static::$activeEvent = null;
        static::$pricingCache = [];
        static::$flashSaleItemCache = [];
    }

    protected function productCacheKey(Product $product): string
    {
        return 'product:' . $product->id;
    }

    protected function normalizeProducts($products): Collection
    {
        if ($products instanceof AbstractPaginator) {
            return $products->getCollection();
        }

        if ($products instanceof Collection) {
            return $products;
        }

        if ($products instanceof Product) {
            return collect([$products]);
        }

        if (is_array($products)) {
            return collect($products);
        }

        if ($products instanceof \Traversable) {
            return collect(iterator_to_array($products));
        }

        return collect();
    }
}