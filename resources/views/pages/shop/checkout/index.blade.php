<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\CartService;

new
#[Layout('components.layouts.shop')]
#[Title('Pembayaran - Compify')]
class extends Component {
    public string $email = '';
    public bool $newsletter = false;

    public string $country = 'Indonesia';
    public string $first_name = '';
    public string $last_name = '';
    public string $company = '';
    public string $address = '';
    public string $district = '';
    public string $city = '';
    public string $province = 'Jawa Tengah';
    public string $postal_code = '';
    public string $phone = '';

    public ?int $shipping_method_id = null;
    public ?int $payment_method_id = null;

    public string $billing_type = 'same';

    public function mount(): void
    {
        $user = auth('customer')->user();

        $this->email = $user?->email ?? '';
        $this->first_name = $user?->name ? explode(' ', $user->name)[0] : '';
        $this->last_name = $user?->name && str_contains($user->name, ' ')
            ? trim(str_replace($this->first_name, '', $user->name))
            : '';

        if (empty(session('cart', []))) {
            $this->redirectRoute('cart.index', navigate: true);
        }
    }

    #[Computed]
    public function cartItems()
    {
        return app(CartService::class)->items();
    }

    #[Computed]
    public function subtotal(): int
    {
        return app(CartService::class)->subtotal();
    }

    #[Computed]
    public function shippingMethods()
    {
        return ShippingMethod::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedShippingMethod()
    {
        return $this->shipping_method_id
            ? ShippingMethod::find($this->shipping_method_id)
            : null;
    }

    #[Computed]
    public function selectedPaymentMethod()
    {
        return $this->payment_method_id
            ? PaymentMethod::find($this->payment_method_id)
            : null;
    }

    #[Computed]
    public function isAddressReady(): bool
    {
        return filled($this->province)
            && filled($this->city)
            && filled($this->district)
            && filled($this->address);
    }

    #[Computed]
    public function shippingCost(): ?int
    {
        if (! $this->selectedShippingMethod || ! $this->isAddressReady) {
            return null;
        }

        return $this->calculateShippingCost($this->selectedShippingMethod, $this->subtotal);
    }

    #[Computed]
    public function total(): int
    {
        return $this->subtotal + ($this->shippingCost ?? 0);
    }

    private function calculateShippingCost(ShippingMethod $method, int $subtotal): int
    {
        if ($method->free_shipping_min && $subtotal >= $method->free_shipping_min) {
            return 0;
        }

        $setting = ShippingSetting::first();

        $customerProvince = strtolower(trim($this->province));
        $customerCity = strtolower(trim($this->city));
        $customerDistrict = strtolower(trim($this->district));

        $baseProvince = strtolower(trim($setting?->province ?? 'Jawa Tengah'));
        $baseCity = strtolower(trim($setting?->city ?? 'Jepara'));
        $baseDistrict = strtolower(trim($setting?->district ?? 'Bangsri'));

        if (
            $customerProvince === $baseProvince &&
            $customerCity === $baseCity &&
            $customerDistrict === $baseDistrict
        ) {
            return $method->same_district_cost ?? $method->base_cost;
        }

        if (
            $customerProvince === $baseProvince &&
            $customerCity === $baseCity
        ) {
            return $method->same_city_cost ?? $method->base_cost;
        }

        if ($customerProvince === $baseProvince) {
            return $method->same_province_cost ?? $method->base_cost;
        }

        return $method->outside_province_cost ?? $method->base_cost;
    }

    private function cartDiscountTotal(): int
    {
        return $this->cartItems->sum(function (array $item) {
            return (int) ($item['discount_amount'] ?? 0) * (int) ($item['quantity'] ?? 1);
        });
    }

    private function createOrderItemSnapshot(Order $order, array $item): void
    {
        if ($item['type'] === 'product') {
            $itemType = ($item['is_event_price'] ?? false) ? 'event_flash_sale' : 'product';

            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => $itemType,
                'product_id' => $item['product_id'],
                'combo_package_id' => null,
                'event_flash_sale_item_id' => $item['event_flash_sale_item_id'] ?? null,

                'product_name' => $item['name'],
                'product_slug' => $item['slug'] ?? null,
                'product_image' => $item['image'] ?? null,

                'price' => $item['unit_price'],
                'original_price' => $item['original_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'price_label' => $item['price_label'] ?? null,

                'quantity' => $item['quantity'],
                'total' => $item['line_total'],

                'snapshot_data' => [
                    'type' => $itemType,
                    'source' => $item['price_source'] ?? null,
                    'product_id' => $item['product_id'],
                    'slug' => $item['slug'] ?? null,
                    'brand_or_category' => $item['brand_or_category'] ?? null,
                    'is_event_price' => (bool) ($item['is_event_price'] ?? false),
                    'event_flash_sale_item_id' => $item['event_flash_sale_item_id'] ?? null,

                    'has_event_stock_limit' => $item['has_event_stock_limit'] ?? false,
                    'event_stock_limit' => $item['event_stock_limit'] ?? null,
                    'event_stock_reserved_before_order' => $item['event_stock_reserved'] ?? 0,
                    'event_stock_remaining_before_order' => $item['event_stock_remaining'] ?? null,

                    'unit_price' => $item['unit_price'],
                    'original_price' => $item['original_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? null,
                ],
            ]);

            Product::whereKey($item['product_id'])->decrement('stock', $item['quantity']);

            return;
        }

        if ($item['type'] === 'combo_package') {
            $children = collect($item['children'])->map(function ($child) use ($item) {
                return [
                    'product_id' => $child['product_id'],
                    'name' => $child['name'],
                    'image' => $child['image'] ?? null,
                    'brand_or_category' => $child['brand_or_category'] ?? null,
                    'quantity_per_package' => (int) $child['quantity'],
                    'package_quantity' => (int) $item['quantity'],
                    'total_quantity' => (int) $child['quantity'] * (int) $item['quantity'],
                    'unit_price' => (int) $child['unit_price'],
                    'line_total_per_package' => (int) $child['line_total'],
                    'line_total_all_package' => (int) $child['line_total'] * (int) $item['quantity'],
                ];
            })->values()->all();

            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => 'combo_package',
                'product_id' => null,
                'combo_package_id' => $item['combo_package_id'],
                'event_flash_sale_item_id' => null,

                'product_name' => $item['name'],
                'product_slug' => $item['slug'] ?? null,
                'product_image' => $item['image'] ?? null,

                'price' => $item['unit_price'],
                'original_price' => $item['original_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'price_label' => $item['price_label'] ?? 'Paket Bundling',

                'quantity' => $item['quantity'],
                'total' => $item['line_total'],

                'snapshot_data' => [
                    'type' => 'combo_package',
                    'combo_package_id' => $item['combo_package_id'],
                    'slug' => $item['slug'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'original_price' => $item['original_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? null,
                    'children' => $children,
                ],
            ]);

            foreach ($item['children'] as $child) {
                if (! $child['product_id']) {
                    continue;
                }

                Product::whereKey($child['product_id'])->decrement(
                    'stock',
                    (int) $child['quantity'] * (int) $item['quantity']
                );
            }
        }
    }

    public function placeOrder()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'country' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'billing_type' => ['required', 'in:same,different'],
        ]);

        if ($this->cartItems->isEmpty()) {
            $this->redirectRoute('cart.index', navigate: true);
            return;
        }

        $invalidItem = $this->cartItems->first(fn (array $item) => ! $item['is_available']);

        if ($invalidItem) {
            $this->addError('cart', $invalidItem['message'] ?? 'Ada item keranjang yang tidak tersedia.');
            return;
        }

        $order = DB::transaction(function () {
            $order = Order::create([
                'user_id' => auth('customer')->id(),
                'shipping_method_id' => $this->shipping_method_id,
                'payment_method_id' => $this->payment_method_id,

                'order_number' => Order::generateOrderNumber(),
                'customer_name' => trim($this->first_name . ' ' . $this->last_name),
                'customer_email' => $this->email,
                'customer_phone' => $this->phone,

                'shipping_address' => $this->address,
                'shipping_province' => $this->province,
                'shipping_city' => $this->city,
                'shipping_district' => $this->district,
                'shipping_postal_code' => $this->postal_code ?: null,

                'subtotal' => $this->subtotal,
                'shipping_cost' => $this->shippingCost ?? 0,
                'discount_amount' => $this->cartDiscountTotal(),
                'total_amount' => $this->total,

                'order_status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($this->cartItems as $item) {
                if (! $item['is_available']) {
                    continue;
                }

                $this->createOrderItemSnapshot($order, $item);
            }

            app(CartService::class)->clear();

            return $order;
        });

        return redirect()->route('checkout.payment', $order);
    }
};
?>

<div class="checkout-page">
    <div class="checkout-shell">
        <section class="checkout-left">
            <div class="checkout-account-row">
                @php
                    $customer = auth('customer')->user();

                    $customerAvatar = $customer?->avatar
                        ? Storage::url($customer->avatar)
                        : asset('assets/user/default-avatar.svg');
                @endphp

                <div class="checkout-user">
                    <span class="checkout-user-avatar">
                        <img src="{{ $customerAvatar }}" alt="{{ $customer?->name ?? 'Customer' }}">
                    </span>

                    <div>
                        <strong>{{ $email ?: $customer?->email ?: 'Email@example.com' }}</strong>
                    </div>
                </div>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="checkout-logout-btn">Keluar</button>
                </form>
            </div>

            <label class="checkout-check">
                <input type="checkbox" wire:model="newsletter">
                <span>Kirimi saya email berita dan penawaran</span>
            </label>

            <form wire:submit="placeOrder" class="checkout-form">
                <div class="checkout-field full floating">
                    <span>Negara/Wilayah</span>
                    <input type="text" wire:model.live="country">
                </div>

                <div class="checkout-grid-2">
                    <label class="checkout-field">
                        <input type="text" wire:model.live="first_name" placeholder="Nama depan">
                    </label>

                    <label class="checkout-field">
                        <input type="text" wire:model.live="last_name" placeholder="Nama belakang">
                    </label>
                </div>

                <label class="checkout-field full">
                    <input type="text" wire:model.live="company" placeholder="Perusahaan (opsional)">
                </label>

                <label class="checkout-field full">
                    <input type="text" wire:model.live="address" placeholder="Alamat">
                </label>

                <label class="checkout-field full">
                    <input type="text" wire:model.live.debounce.400ms="district" placeholder="Kecamatan">
                </label>

                <label class="checkout-field full">
                    <input type="text" wire:model.live.debounce.400ms="city" placeholder="Kota">
                </label>

                <div class="checkout-grid-2">
                    <label class="checkout-field">
                        <input type="text" wire:model.live.debounce.400ms="province" placeholder="Provinsi">
                    </label>

                    <label class="checkout-field">
                        <input type="text" wire:model.live="postal_code" placeholder="Kode Pos">
                    </label>
                </div>

                <label class="checkout-field full">
                    <input type="text" wire:model.live="phone" placeholder="Telepon">
                </label>

                <div class="checkout-section">
                    <h2>Metode Pengiriman</h2>

                    @if(! $this->isAddressReady)
                        <div class="checkout-muted-box">
                            Masukkan alamat pengiriman Anda untuk melihat metode pengiriman yang tersedia.
                        </div>
                    @else
                        <div class="checkout-method-list">
                            @forelse($this->shippingMethods as $method)
                                @php
                                    $cost = $this->calculateShippingCost($method, $this->subtotal);
                                @endphp

                                <label @class([
                                    'checkout-method-card',
                                    'active' => (int) $shipping_method_id === $method->id,
                                ])>
                                    <input type="radio" wire:model.live="shipping_method_id" value="{{ $method->id }}">

                                    <div>
                                        <strong>{{ $method->name }}</strong>
                                        <small>{{ $method->estimate ?: 'Estimasi menyesuaikan wilayah' }}</small>
                                        @if($method->description)
                                            <p>{{ $method->description }}</p>
                                        @endif
                                    </div>

                                    <b>
                                        @if($cost <= 0)
                                            Gratis
                                        @else
                                            Rp {{ number_format($cost, 0, ',', '.') }}
                                        @endif
                                    </b>
                                </label>
                            @empty
                                <div class="checkout-muted-box">
                                    Belum ada metode pengiriman aktif.
                                </div>
                            @endforelse
                        </div>
                    @endif

                    @error('shipping_method_id')
                        <small class="checkout-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="checkout-section">
                    <h2>Pembayaran</h2>
                    <p>Semua transaksi sudah diamankan. Detail pembayaran akan muncul setelah order dibuat.</p>

                    <div class="checkout-method-list">
                        @forelse($this->paymentMethods as $method)
                            @php
                                $logo = data_get($method, 'logo');
                            @endphp

                            <label @class([
                                'checkout-method-card',
                                'active' => (int) $payment_method_id === $method->id,
                            ])>
                                <input type="radio" wire:model.live="payment_method_id" value="{{ $method->id }}">

                                <div>
                                    <strong>{{ $method->name }}</strong>
                                    <small>{{ data_get($method, 'description') ?: 'Pilih metode pembayaran' }}</small>
                                </div>

                                @if($logo)
                                    <img src="{{ Storage::url($logo) }}" alt="{{ $method->name }}">
                                @endif
                            </label>
                        @empty
                            <div class="checkout-muted-box">
                                Belum ada metode pembayaran aktif.
                            </div>
                        @endforelse
                    </div>

                    @error('payment_method_id')
                        <small class="checkout-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="checkout-section">
                    <h2>Alamat Penagihan</h2>

                    <div class="checkout-billing-box">
                        <label @class(['active' => $billing_type === 'same'])>
                            <input type="radio" wire:model="billing_type" value="same">
                            <span>Sama dengan alamat pengiriman</span>
                        </label>

                        <label @class(['active' => $billing_type === 'different'])>
                            <input type="radio" wire:model="billing_type" value="different">
                            <span>Pakai alamat penagihan lain</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="checkout-pay-button">
                    Bayar Sekarang
                </button>
            </form>
        </section>

        <aside class="checkout-right">
            <div class="checkout-summary">
                @foreach($this->cartItems as $item)
                    @php
                        $image = ($item['image'] ?? null) ? Storage::url($item['image']) : null;
                    @endphp

                    <div class="checkout-summary-product">
                        <div class="checkout-product-image">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $item['name'] }}">
                            @else
                                <span>{{ substr($item['name'], 0, 2) }}</span>
                            @endif

                            <b>{{ $item['quantity'] }}</b>
                        </div>

                        <div>
                            <strong>{{ $item['name'] }}</strong>
                            <small>{{ $item['brand_or_category'] ?? ($item['type'] === 'combo_package' ? 'Paket Bundling' : 'Produk') }}</small>

                            @if($item['type'] === 'combo_package' && $item['children']->isNotEmpty())
                                <div class="checkout-combo-mini">
                                    @foreach($item['children']->take(3) as $child)
                                        <span>{{ $child['quantity'] }}x {{ $child['name'] }}</span>
                                    @endforeach

                                    @if($item['children']->count() > 3)
                                        <span>+{{ $item['children']->count() - 3 }} produk lain</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <p>Rp {{ number_format($item['line_total'], 0, ',', '.') }}</p>
                    </div>
                @endforeach

                <div class="checkout-discount-row">
                    <input type="text" placeholder="Kode diskon">
                    <button type="button">Pakai</button>
                </div>

                <div class="checkout-price-list">
                    <div>
                        <span>Subtotal</span>
                        <strong>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</strong>
                    </div>

                    <div>
                        <span>Pengiriman</span>

                        @if($this->shippingCost === null)
                            <strong class="muted">Masukkan alamat pengiriman</strong>
                        @elseif($this->shippingCost <= 0)
                            <strong>Gratis</strong>
                        @else
                            <strong>Rp {{ number_format($this->shippingCost, 0, ',', '.') }}</strong>
                        @endif
                    </div>
                </div>

                <div class="checkout-total-row">
                    <span>Total</span>
                    <strong>Rp {{ number_format($this->total, 0, ',', '.') }}</strong>
                </div>
            </div>
        </aside>
    </div>
</div>