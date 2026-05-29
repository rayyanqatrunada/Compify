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
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return collect();
        }

        $products = Product::with(['brand', 'category'])
            ->whereIn('id', array_keys($cart))
            ->get();

        return $products->map(function ($product) use ($cart) {
            $qty = (int) ($cart[$product->id] ?? 1);

            return [
                'product' => $product,
                'quantity' => $qty,
                'price' => (int) $product->final_price,
                'line_total' => (int) $product->final_price * $qty,
            ];
        });
    }

    #[Computed]
    public function subtotal(): int
    {
        return $this->cartItems->sum('line_total');
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

        if ($this->shippingCost === null) {
            $this->addError('shipping_method_id', 'Lengkapi alamat dan pilih metode pengiriman.');
            return;
        }

        $order = DB::transaction(function () {
            $order = Order::create([
                'user_id' => auth('customer')->id(),
                'shipping_method_id' => $this->shipping_method_id,
                'payment_method_id' => $this->payment_method_id,

                'name' => trim($this->first_name . ' ' . $this->last_name),
                'email' => $this->email,
                'phone' => $this->phone,

                'shipping_address' => $this->address,
                'shipping_province' => $this->province,
                'shipping_city' => $this->city,
                'shipping_district' => $this->district,
                'shipping_postal_code' => $this->postal_code ?: null,

                'subtotal' => $this->subtotal,
                'shipping_cost' => $this->shippingCost ?? 0,
                'total' => $this->total,

                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['line_total'],
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            session()->forget('cart');

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
                        $product = $item['product'];
                        $image = $product->image ? Storage::url($product->image) : null;
                    @endphp

                    <div class="checkout-summary-product">
                        <div class="checkout-product-image">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $product->name }}">
                            @else
                                <span>{{ substr($product->name, 0, 2) }}</span>
                            @endif

                            <b>{{ $item['quantity'] }}</b>
                        </div>

                        <div>
                            <strong>{{ $product->name }}</strong>
                            <small>{{ $product->brand?->name ?? $product->category?->name }}</small>
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