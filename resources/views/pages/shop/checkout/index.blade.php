<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
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
    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    public string $shipping_address = '';
    public string $shipping_city = '';
    public string $shipping_province = '';
    public string $shipping_postal_code = '';

    public ?int $payment_method_id = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        abort_if(! $customer, 403);

        $this->customer_name = $customer->name ?? '';
        $this->customer_email = $customer->email ?? '';
        $this->customer_phone = $customer->phone ?? '';
        $this->shipping_address = $customer->address ?? '';
        $this->shipping_city = $customer->city ?? '';
        $this->shipping_province = $customer->province ?? '';
        $this->shipping_postal_code = $customer->postal_code ?? '';

        $this->payment_method_id = PaymentMethod::active()
            ->orderBy('sort_order')
            ->value('id');
    }

    #[Computed]
    public function cartItems()
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return collect();
        }

        return Product::with(['brand', 'category'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->map(function ($product) use ($cart) {
                $quantity = $cart[$product->id] ?? 1;
                $price = $product->sale_price ?: $product->price;

                $product->cart_quantity = $quantity;
                $product->cart_price = $price;
                $product->cart_total = $price * $quantity;

                return $product;
            });
    }

    #[Computed]
    public function subtotal(): int
    {
        return (int) $this->cartItems->sum('cart_total');
    }

    #[Computed]
    public function shippingCost(): int
    {
        return 0;
    }

    #[Computed]
    public function total(): int
    {
        return $this->subtotal + $this->shippingCost;
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::active()
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function selectedPaymentMethod()
    {
        return PaymentMethod::find($this->payment_method_id);
    }

    public function placeOrder()
    {
        if ($this->cartItems->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $this->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_province' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ]);

        $customer = Auth::guard('customer')->user();
        $method = PaymentMethod::findOrFail($this->payment_method_id);

        $order = DB::transaction(function () use ($customer, $method) {
            $order = Order::create([
                'user_id' => $customer->id,
                'payment_method_id' => $method->id,
                'order_number' => 'CPF-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_type' => $method->type,
                'payment_reference' => null,
                'payment_redirect_url' => $method->type === 'url' ? $method->payment_url : null,
                'customer_name' => $this->customer_name,
                'customer_email' => $this->customer_email,
                'customer_phone' => $this->customer_phone,
                'shipping_address' => $this->shipping_address,
                'shipping_city' => $this->shipping_city,
                'shipping_province' => $this->shipping_province,
                'shipping_postal_code' => $this->shipping_postal_code,
                'subtotal' => $this->subtotal,
                'shipping_cost' => $this->shippingCost,
                'total' => $this->total,
            ]);

            foreach ($this->cartItems as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->cart_price,
                    'quantity' => $product->cart_quantity,
                    'total' => $product->cart_total,
                ]);
            }

            return $order;
        });

        session()->forget('cart');

        if ($method->type === 'url' && $method->payment_url) {
            return redirect()->away($method->payment_url);
        }

        return redirect()->route('checkout.success', $order);
    }
};
?>

<div class="checkout-page">
    <section class="checkout-form-side">
        <h1>Pembayaran</h1>
        <p>Selesaikan informasi pengiriman dan pilih metode pembayaran.</p>

        <form wire:submit="placeOrder" class="checkout-form">
            <h2>Kontak</h2>

            <input type="email" wire:model="customer_email" placeholder="Email">
            <input type="text" wire:model="customer_phone" placeholder="Nomor HP">

            <h2>Pengantaran</h2>

            <input type="text" wire:model="customer_name" placeholder="Nama lengkap">
            <input type="text" wire:model="shipping_address" placeholder="Alamat">
            <input type="text" wire:model="shipping_city" placeholder="Kota">
            <input type="text" wire:model="shipping_province" placeholder="Provinsi">
            <input type="text" wire:model="shipping_postal_code" placeholder="Kode pos">

            <h2>Metode Pembayaran</h2>

            <div class="payment-method-list">
                @forelse($this->paymentMethods as $method)
                    <label class="payment-method-card">
                        <input type="radio" wire:model.live="payment_method_id" value="{{ $method->id }}">

                        @if($method->logo)
                            <img src="{{ Storage::url($method->logo) }}" alt="{{ $method->name }}">
                        @endif

                        <span>
                            <strong>{{ $method->name }}</strong>
                            <small>{{ strtoupper($method->type) }}</small>
                        </span>
                    </label>
                @empty
                    <div class="checkout-empty">
                        Belum ada metode pembayaran aktif. Tambahkan dari admin.
                    </div>
                @endforelse
            </div>

            @if($this->selectedPaymentMethod)
                <div class="payment-instruction-box">
                    <h3>{{ $this->selectedPaymentMethod->name }}</h3>

                    @if($this->selectedPaymentMethod->type === 'qr' && $this->selectedPaymentMethod->qr_image)
                        <img src="{{ Storage::url($this->selectedPaymentMethod->qr_image) }}" class="payment-qr-image" alt="QR Payment">
                    @endif

                    @if($this->selectedPaymentMethod->instructions)
                        <p>{!! nl2br(e($this->selectedPaymentMethod->instructions)) !!}</p>
                    @endif

                    @if($this->selectedPaymentMethod->type === 'url')
                        <small>Setelah klik bayar, kamu akan diarahkan ke halaman pembayaran.</small>
                    @endif

                    @if($this->selectedPaymentMethod->type === 'api')
                        <small>Metode API siap dipakai. Integrasi gateway asli bisa ditambahkan di service payment.</small>
                    @endif
                </div>
            @endif

            <button type="submit" class="checkout-pay-button">
                Bayar sekarang
            </button>
        </form>
    </section>

    <aside class="checkout-summary-side">
        @foreach($this->cartItems as $item)
            <div class="checkout-summary-item">
                <div class="checkout-summary-image">
                    @if($item->image)
                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                    @endif

                    <span>{{ $item->cart_quantity }}</span>
                </div>

                <div>
                    <h3>{{ $item->name }}</h3>
                    <p>{{ $item->brand?->name ?? $item->category?->name }}</p>
                </div>

                <strong>Rp {{ number_format($item->cart_total, 0, ',', '.') }}</strong>
            </div>
        @endforeach

        <div class="checkout-summary-row">
            <span>Subtotal</span>
            <strong>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</strong>
        </div>

        <div class="checkout-summary-row">
            <span>Pengiriman</span>
            <strong>Rp {{ number_format($this->shippingCost, 0, ',', '.') }}</strong>
        </div>

        <div class="checkout-summary-total">
            <span>Total</span>
            <strong>Rp {{ number_format($this->total, 0, ',', '.') }}</strong>
        </div>
    </aside>
</div>