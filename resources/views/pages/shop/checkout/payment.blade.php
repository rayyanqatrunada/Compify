<?php

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Detail Pembayaran - Compify')]
class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        abort_if($order->user_id !== auth('customer')->id(), 403);

        $this->order = $order->load(['items.product', 'paymentMethod', 'shippingMethod']);
    }
};
?>

<div class="payment-detail-page">
    <div class="payment-detail-shell">
        <section class="payment-detail-card">
            <p class="payment-kicker">Order berhasil dibuat</p>
            <h1>Detail Pembayaran</h1>

            <div class="payment-order-box">
                <div>
                    <span>Nomor Order</span>
                    <strong>{{ $order->order_number ?? ('#' . $order->id) }}</strong>
                </div>

                <div>
                    <span>Total Pembayaran</span>
                    <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                </div>

                <div>
                    <span>Status</span>
                    <strong>{{ ucfirst($order->payment_status ?? 'pending') }}</strong>
                </div>
            </div>

            @php
                $method = $order->paymentMethod;
                $type = data_get($method, 'type') ?? data_get($method, 'method_type') ?? 'manual';
                $logo = data_get($method, 'logo');
                $qrImage = data_get($method, 'qr_image') ?? data_get($method, 'image');
                $paymentUrl = data_get($method, 'payment_url') ?? data_get($method, 'url');
                $accountNumber = data_get($method, 'account_number');
                $accountName = data_get($method, 'account_name');
                $instructions = data_get($method, 'instructions') ?? data_get($method, 'description');
            @endphp

            <div class="payment-method-detail">
                <div class="payment-method-title">
                    @if($logo)
                        <img src="{{ Storage::url($logo) }}" alt="{{ $method?->name }}">
                    @endif

                    <div>
                        <span>Metode Pembayaran</span>
                        <strong>{{ $method?->name ?? 'Pembayaran Manual' }}</strong>
                    </div>
                </div>

                @if($qrImage)
                    <div class="payment-qr-box">
                        <img src="{{ Storage::url($qrImage) }}" alt="QR Pembayaran">
                        <p>Scan QR setelah memastikan nominal pembayaran sudah benar.</p>
                    </div>
                @endif

                @if($paymentUrl)
                    <a href="{{ $paymentUrl }}" target="_blank" class="payment-url-button">
                        Buka Link Pembayaran
                    </a>
                @endif

                @if($accountNumber || $accountName)
                    <div class="payment-bank-box">
                        @if($accountNumber)
                            <div>
                                <span>Nomor Rekening / VA</span>
                                <strong>{{ $accountNumber }}</strong>
                            </div>
                        @endif

                        @if($accountName)
                            <div>
                                <span>Atas Nama</span>
                                <strong>{{ $accountName }}</strong>
                            </div>
                        @endif
                    </div>
                @endif

                @if($instructions)
                    <div class="payment-instruction">
                        <h2>Instruksi</h2>
                        <div>{!! nl2br(e($instructions)) !!}</div>
                    </div>
                @endif
            </div>
        </section>

        <aside class="payment-detail-summary">
            <h2>Ringkasan Order</h2>

            @foreach($order->items as $item)
                <div class="payment-summary-item">
                    <span>{{ $item->quantity }}x {{ $item->product_name ?? $item->product?->name }}</span>
                    <strong>Rp {{ number_format($item->total, 0, ',', '.') }}</strong>
                </div>
            @endforeach

            <hr>

            <div class="payment-summary-item">
                <span>Subtotal</span>
                <strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong>
            </div>

            <div class="payment-summary-item">
                <span>Pengiriman</span>
                <strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong>
            </div>

            <div class="payment-summary-total">
                <span>Total</span>
                <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
            </div>
        </aside>
    </div>
</div>