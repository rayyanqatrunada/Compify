<x-layouts.shop>
    <div class="checkout-success-page">
        <div class="checkout-success-card">
            <h1>Pesanan berhasil dibuat</h1>
            <p>Nomor pesanan kamu:</p>
            <strong>{{ $order->order_number }}</strong>

            <p>Status pembayaran: {{ $order->payment_status }}</p>

            <a href="{{ route('home') }}" wire:navigate>Kembali ke Beranda</a>
        </div>
    </div>
</x-layouts.shop>