<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderInventoryService
{
    public function reserveProductStock(int $productId, int $quantity, string $name = 'Produk'): void
    {
        $quantity = max(0, $quantity);

        if ($quantity < 1) {
            return;
        }

        $affected = Product::query()
            ->whereKey($productId)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($affected < 1) {
            throw ValidationException::withMessages([
                'cart' => "Stok {$name} tidak cukup. Silakan cek ulang keranjang.",
            ]);
        }
    }

    public function markReserved(Order $order): void
    {
        if (! $order->stock_reserved_at) {
            $order->forceFill(['stock_reserved_at' => now()])->save();
        }
    }

    public function restore(Order $order): void
    {
        DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->stock_reserved_at || $lockedOrder->stock_restored_at) {
                return;
            }

            $lockedOrder->loadMissing('items');

            foreach ($lockedOrder->items as $item) {
                if ($item->item_type === 'combo_package') {
                    foreach ((array) data_get($item->snapshot_data, 'children', []) as $child) {
                        $productId = (int) ($child['product_id'] ?? 0);
                        $quantity = (int) ($child['total_quantity'] ?? 0);

                        if ($productId > 0 && $quantity > 0) {
                            Product::query()->whereKey($productId)->increment('stock', $quantity);
                        }
                    }

                    continue;
                }

                if ($item->product_id && $item->quantity > 0) {
                    Product::query()->whereKey($item->product_id)->increment('stock', (int) $item->quantity);
                }
            }

            $lockedOrder->forceFill(['stock_restored_at' => now()])->save();
        });
    }
}
