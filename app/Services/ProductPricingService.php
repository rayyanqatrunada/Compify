<?php

namespace App\Services;

use App\Models\EventFlashSaleItem;
use App\Models\EventSetting;
use App\Models\Product;

class ProductPricingService
{
    protected array $cache = [];

    public function forProduct(Product $product): array
    {
        if (! $product->exists) {
            return $this->regularPricing($product);
        }

        $cacheKey = 'product:' . $product->id;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $regular = $this->regularPricing($product);

        if (! EventSetting::activeNow()) {
            return $this->cache[$cacheKey] = $regular;
        }

        $flashSaleItem = $this->activeFlashSaleItemForProduct($product);

        if (! $flashSaleItem) {
            return $this->cache[$cacheKey] = $regular;
        }

        $eventPrice = $this->discountedPrice(
            basePrice: $regular['unit_price'],
            discountType: $flashSaleItem->discount_type,
            discountValue: $flashSaleItem->discount_value,
        );

        if ($eventPrice >= $regular['unit_price']) {
            return $this->cache[$cacheKey] = $regular;
        }

        $discountAmount = max(0, $regular['unit_price'] - $eventPrice);
        $discountPercent = $regular['unit_price'] > 0
            ? (int) round(($discountAmount / $regular['unit_price']) * 100)
            : 0;

        return $this->cache[$cacheKey] = [
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

        return EventFlashSaleItem::query()
            ->where('product_id', $product->id)
            ->where('event_flash_sale_items.is_active', true)
            ->whereHas('group', function ($query) {
                $query->where('event_flash_sale_groups.is_active', true);
            })
            ->orderBy('event_flash_sale_items.sort_order')
            ->orderBy('event_flash_sale_items.id')
            ->first();
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
}