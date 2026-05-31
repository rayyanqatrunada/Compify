<?php

namespace App\Services;

use App\Models\EventFlashSaleItem;
use App\Models\OrderItem;
use App\Models\Product;

class EventFlashSaleStockService
{
    public function reservedQuantity(EventFlashSaleItem $item): int
    {
        return (int) OrderItem::query()
            ->where('event_flash_sale_item_id', $item->id)
            ->whereHas('order', function ($query) {
                $query
                    ->whereNotIn('payment_status', ['failed', 'expired', 'refunded'])
                    ->where('order_status', '!=', 'cancelled');
            })
            ->sum('quantity');
    }

    public function remainingForItem(EventFlashSaleItem $item): ?int
    {
        if ($item->stock_limit === null) {
            return null;
        }

        return max(0, (int) $item->stock_limit - $this->reservedQuantity($item));
    }

    public function availableForItem(EventFlashSaleItem $item): bool
    {
        $remaining = $this->remainingForItem($item);

        return $remaining === null || $remaining > 0;
    }

    public function maxPurchasableForProduct(Product $product): int
    {
        $productStock = max(0, (int) $product->stock);

        if (! $product->is_event_price) {
            return $productStock;
        }

        $flashSaleItem = $product->active_event_flash_sale_item;

        if (! $flashSaleItem) {
            return $productStock;
        }

        $remaining = $this->remainingForItem($flashSaleItem);

        if ($remaining === null) {
            return $productStock;
        }

        return min($productStock, $remaining);
    }

    public function infoForProduct(Product $product): array
    {
        $flashSaleItem = $product->active_event_flash_sale_item;

        if (! $product->is_event_price || ! $flashSaleItem) {
            return [
                'has_event_stock_limit' => false,
                'event_stock_limit' => null,
                'event_stock_reserved' => 0,
                'event_stock_remaining' => null,
            ];
        }

        return [
            'has_event_stock_limit' => $flashSaleItem->stock_limit !== null,
            'event_stock_limit' => $flashSaleItem->stock_limit,
            'event_stock_reserved' => $this->reservedQuantity($flashSaleItem),
            'event_stock_remaining' => $this->remainingForItem($flashSaleItem),
        ];
    }
}