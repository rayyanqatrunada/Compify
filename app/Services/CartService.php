<?php

namespace App\Services;

use App\Models\ComboPackage;
use App\Models\EventSetting;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'cart';

    public function cart(): array
    {
        $cart = $this->normalizeCart(session()->get(self::SESSION_KEY, []));

        session()->put(self::SESSION_KEY, $cart);

        return $cart;
    }

    public function addProduct(Product $product, int $quantity = 1): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'cart' => 'Produk tidak aktif.',
            ]);
        }

        if ($product->stock < 1) {
            throw ValidationException::withMessages([
                'cart' => 'Stok produk habis.',
            ]);
        }

        $quantity = max(1, $quantity);
        $cart = $this->cart();

        $key = $this->productKey($product->id);
        $currentQty = (int) ($cart[$key]['quantity'] ?? 0);

        $cart[$key] = [
            'type' => 'product',
            'product_id' => $product->id,
            'quantity' => min($currentQty + $quantity, (int) $product->stock),
        ];

        session()->put(self::SESSION_KEY, $cart);
    }

    public function addComboPackage(ComboPackage $comboPackage, int $quantity = 1): void
    {
        if (! EventSetting::activeNow()) {
            throw ValidationException::withMessages([
                'cart' => 'Paket bundling hanya tersedia saat event aktif.',
            ]);
        }

        if (! $comboPackage->is_active) {
            throw ValidationException::withMessages([
                'cart' => 'Paket bundling tidak aktif.',
            ]);
        }

        $comboPackage->loadMissing(['items.product.brand', 'items.product.category']);

        if ($comboPackage->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Paket bundling belum memiliki produk.',
            ]);
        }

        $maxQuantity = $this->comboMaxQuantity($comboPackage);

        if ($maxQuantity < 1) {
            throw ValidationException::withMessages([
                'cart' => 'Stok produk dalam paket tidak mencukupi.',
            ]);
        }

        $quantity = max(1, $quantity);
        $cart = $this->cart();

        $key = $this->comboKey($comboPackage->id);
        $currentQty = (int) ($cart[$key]['quantity'] ?? 0);

        $cart[$key] = [
            'type' => 'combo_package',
            'combo_package_id' => $comboPackage->id,
            'quantity' => min($currentQty + $quantity, $maxQuantity),
        ];

        session()->put(self::SESSION_KEY, $cart);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        $cart = $this->cart();

        if (! isset($cart[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
            session()->put(self::SESSION_KEY, $cart);
            return;
        }

        $quantity = max(1, $quantity);

        if (($cart[$key]['type'] ?? null) === 'product') {
            $product = Product::find($cart[$key]['product_id'] ?? null);

            if (! $product || ! $product->is_active) {
                unset($cart[$key]);
                session()->put(self::SESSION_KEY, $cart);
                return;
            }

            $cart[$key]['quantity'] = min($quantity, max(1, (int) $product->stock));
        }

        if (($cart[$key]['type'] ?? null) === 'combo_package') {
            $comboPackage = ComboPackage::with(['items.product'])->find($cart[$key]['combo_package_id'] ?? null);

            if (! $comboPackage || ! $comboPackage->is_active || ! EventSetting::activeNow()) {
                unset($cart[$key]);
                session()->put(self::SESSION_KEY, $cart);
                return;
            }

            $cart[$key]['quantity'] = min($quantity, max(1, $this->comboMaxQuantity($comboPackage)));
        }

        session()->put(self::SESSION_KEY, $cart);
    }

    public function remove(string $key): void
    {
        $cart = $this->cart();

        unset($cart[$key]);

        session()->put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return collect($this->cart())->sum(fn (array $item) => (int) ($item['quantity'] ?? 0));
    }

    public function items(): Collection
    {
        return collect($this->cart())
            ->map(function (array $item, string $key) {
                return match ($item['type'] ?? null) {
                    'product' => $this->buildProductItem($key, $item),
                    'combo_package' => $this->buildComboItem($key, $item),
                    default => null,
                };
            })
            ->filter()
            ->values();
    }

    public function availableItems(): Collection
    {
        return $this->items()
            ->filter(fn (array $item) => (bool) $item['is_available'])
            ->values();
    }

    public function subtotal(): int
    {
        return $this->availableItems()->sum(fn (array $item) => (int) $item['line_total']);
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public function productKey(int $productId): string
    {
        return 'product:' . $productId;
    }

    public function comboKey(int $comboPackageId): string
    {
        return 'combo:' . $comboPackageId;
    }

    private function buildProductItem(string $key, array $item): array
    {
        $product = Product::with(['brand', 'category'])->find($item['product_id'] ?? null);
        $quantity = max(1, (int) ($item['quantity'] ?? 1));

        if (! $product) {
            return [
                'key' => $key,
                'type' => 'product',
                'is_available' => false,
                'message' => 'Produk tidak ditemukan.',
                'name' => 'Produk tidak ditemukan',
                'image' => null,
                'quantity' => $quantity,
                'unit_price' => 0,
                'original_price' => 0,
                'discount_amount' => 0,
                'discount_percent' => null,
                'line_total' => 0,
                'children' => collect(),
            ];
        }

        $isAvailable = $product->is_active && $product->stock > 0;
        $quantity = min($quantity, max(1, (int) $product->stock));

        return [
            'key' => $key,
            'type' => 'product',
            'is_available' => $isAvailable,
            'message' => $isAvailable ? null : 'Produk tidak aktif atau stok habis.',

            'product' => $product,
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->image,
            'brand_or_category' => $product->brand?->name ?? $product->category?->name,

            'quantity' => $quantity,
            'max_quantity' => max(0, (int) $product->stock),

            'unit_price' => $isAvailable ? (int) $product->final_price : 0,
            'original_price' => $isAvailable ? (int) $product->original_price : 0,
            'discount_amount' => $isAvailable ? (int) $product->discount_amount : 0,
            'discount_percent' => $isAvailable ? $product->discount_percent : null,
            'is_event_price' => $isAvailable ? (bool) $product->is_event_price : false,
            'event_flash_sale_item_id' => $isAvailable ? $product->event_flash_sale_item_id : null,
            'price_source' => $isAvailable ? $product->price_source : null,
            'price_label' => $isAvailable ? $product->price_source_label : null,

            'line_total' => $isAvailable ? (int) $product->final_price * $quantity : 0,
            'children' => collect(),
        ];
    }

    private function buildComboItem(string $key, array $item): array
    {
        $comboPackage = ComboPackage::with(['items.product.brand', 'items.product.category'])
            ->find($item['combo_package_id'] ?? null);

        $quantity = max(1, (int) ($item['quantity'] ?? 1));

        if (! $comboPackage) {
            return [
                'key' => $key,
                'type' => 'combo_package',
                'is_available' => false,
                'message' => 'Paket bundling tidak ditemukan.',
                'name' => 'Paket bundling tidak ditemukan',
                'image' => null,
                'quantity' => $quantity,
                'unit_price' => 0,
                'original_price' => 0,
                'discount_amount' => 0,
                'discount_percent' => null,
                'line_total' => 0,
                'children' => collect(),
            ];
        }

        $eventIsRunning = (bool) EventSetting::activeNow();
        $maxQuantity = $this->comboMaxQuantity($comboPackage);
        $isAvailable = $eventIsRunning && $comboPackage->is_active && $comboPackage->items->isNotEmpty() && $maxQuantity > 0;

        $quantity = min($quantity, max(1, $maxQuantity));

        $unitPrice = $isAvailable
            ? (int) ($comboPackage->calculated_package_price ?: $comboPackage->package_price)
            : 0;

        $originalPrice = $isAvailable
            ? (int) $comboPackage->original_total
            : 0;

        $discountAmount = $isAvailable
            ? max(0, $originalPrice - $unitPrice)
            : 0;

        $children = $comboPackage->items->map(function ($packageItem) {
            $product = $packageItem->product;
            $childQty = max(1, (int) $packageItem->quantity);
            $unitPrice = (int) ($product?->final_price ?? 0);

            return [
                'product_id' => $product?->id,
                'name' => $product?->name ?? 'Produk tidak ditemukan',
                'image' => $product?->image,
                'brand_or_category' => $product?->brand?->name ?? $product?->category?->name,
                'quantity' => $childQty,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $childQty,
                'is_available' => (bool) ($product && $product->is_active && $product->stock >= $childQty),
            ];
        });

        return [
            'key' => $key,
            'type' => 'combo_package',
            'is_available' => $isAvailable,
            'message' => $isAvailable ? null : 'Paket bundling tidak aktif, event selesai, atau stok tidak cukup.',

            'combo_package' => $comboPackage,
            'combo_package_id' => $comboPackage->id,
            'name' => $comboPackage->name,
            'slug' => $comboPackage->slug,
            'image' => $comboPackage->image,
            'brand_or_category' => 'Paket Bundling',

            'quantity' => $quantity,
            'max_quantity' => max(0, $maxQuantity),

            'unit_price' => $unitPrice,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'discount_percent' => $originalPrice > 0 && $discountAmount > 0
                ? (int) round(($discountAmount / $originalPrice) * 100)
                : null,
            'is_event_price' => true,
            'event_flash_sale_item_id' => null,
            'price_source' => 'combo_package',
            'price_label' => 'Paket Bundling',

            'line_total' => $unitPrice * $quantity,
            'children' => $children,
        ];
    }

    private function comboMaxQuantity(ComboPackage $comboPackage): int
    {
        $comboPackage->loadMissing(['items.product']);

        if ($comboPackage->items->isEmpty()) {
            return 0;
        }

        $max = null;

        foreach ($comboPackage->items as $item) {
            $product = $item->product;
            $neededPerPackage = max(1, (int) $item->quantity);

            if (! $product || ! $product->is_active || $product->stock < 1) {
                return 0;
            }

            $possible = intdiv((int) $product->stock, $neededPerPackage);

            $max = is_null($max) ? $possible : min($max, $possible);
        }

        return max(0, (int) $max);
    }

    private function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $key => $value) {
            if (is_numeric($key)) {
                $this->mergeNormalizedItem(
                    normalized: $normalized,
                    key: $this->productKey((int) $key),
                    type: 'product',
                    idField: 'product_id',
                    id: (int) $key,
                    quantity: is_array($value) ? (int) ($value['quantity'] ?? 1) : (int) $value,
                );

                continue;
            }

            if (is_string($key) && str_starts_with($key, 'product:')) {
                $productId = (int) str_replace('product:', '', $key);

                $this->mergeNormalizedItem(
                    normalized: $normalized,
                    key: $this->productKey($productId),
                    type: 'product',
                    idField: 'product_id',
                    id: $productId,
                    quantity: is_array($value) ? (int) ($value['quantity'] ?? 1) : 1,
                );

                continue;
            }

            if (is_string($key) && str_starts_with($key, 'regular:')) {
                $productId = (int) str_replace('regular:', '', $key);

                $this->mergeNormalizedItem(
                    normalized: $normalized,
                    key: $this->productKey($productId),
                    type: 'product',
                    idField: 'product_id',
                    id: $productId,
                    quantity: is_array($value) ? (int) ($value['quantity'] ?? 1) : 1,
                );

                continue;
            }

            if (is_string($key) && preg_match('/^flash:\d+:product:(\d+)$/', $key, $matches)) {
                $productId = (int) $matches[1];

                $this->mergeNormalizedItem(
                    normalized: $normalized,
                    key: $this->productKey($productId),
                    type: 'product',
                    idField: 'product_id',
                    id: $productId,
                    quantity: is_array($value) ? (int) ($value['quantity'] ?? 1) : 1,
                );

                continue;
            }

            if (is_string($key) && str_starts_with($key, 'combo:')) {
                $comboPackageId = (int) str_replace('combo:', '', $key);

                $this->mergeNormalizedItem(
                    normalized: $normalized,
                    key: $this->comboKey($comboPackageId),
                    type: 'combo_package',
                    idField: 'combo_package_id',
                    id: $comboPackageId,
                    quantity: is_array($value) ? (int) ($value['quantity'] ?? 1) : 1,
                );

                continue;
            }

            if (is_array($value)) {
                $type = $value['type'] ?? $value['source_type'] ?? null;

                if (in_array($type, ['product', 'regular', 'flash_sale'], true) && isset($value['product_id'])) {
                    $productId = (int) $value['product_id'];

                    $this->mergeNormalizedItem(
                        normalized: $normalized,
                        key: $this->productKey($productId),
                        type: 'product',
                        idField: 'product_id',
                        id: $productId,
                        quantity: (int) ($value['quantity'] ?? 1),
                    );

                    continue;
                }

                if ($type === 'combo_package' && isset($value['combo_package_id'])) {
                    $comboPackageId = (int) $value['combo_package_id'];

                    $this->mergeNormalizedItem(
                        normalized: $normalized,
                        key: $this->comboKey($comboPackageId),
                        type: 'combo_package',
                        idField: 'combo_package_id',
                        id: $comboPackageId,
                        quantity: (int) ($value['quantity'] ?? 1),
                    );

                    continue;
                }
            }
        }

        return $normalized;
    }

    private function mergeNormalizedItem(
        array &$normalized,
        string $key,
        string $type,
        string $idField,
        int $id,
        int $quantity
    ): void {
        if ($id < 1 || $quantity < 1) {
            return;
        }

        $normalized[$key] ??= [
            'type' => $type,
            $idField => $id,
            'quantity' => 0,
        ];

        $normalized[$key]['quantity'] += $quantity;
    }
}