<?php

use App\Models\Order;
use App\Services\WhatsAppOrderMessageService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Detail Pembayaran - Compify')]
class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        abort_if($order->user_id !== auth('customer')->id(), 403);

        $this->order = $order->load([
            'items.product',
            'items.comboPackage',
            'items.flashSaleItem',
            'paymentMethod',
            'shippingMethod',
        ]);
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public function isMidtrans(): bool
    {
        $method = $this->order->paymentMethod;

        return $method
            && $method->type === 'api'
            && strtolower((string) $method->api_provider) === 'midtrans';
    }

    public function paymentUrl(): ?string
    {
        $method = $this->order->paymentMethod;

        return $this->order->payment_redirect_url
            ?: (data_get($method, 'payment_url') ?? data_get($method, 'url'));
    }

    public function whatsappUrl(): ?string
    {
        $method = $this->order->paymentMethod;

        if (! $method || $method->type !== 'whatsapp') {
            return null;
        }

        if ($this->order->payment_redirect_url) {
            return $this->order->payment_redirect_url;
        }

        return app(WhatsAppOrderMessageService::class)
            ->urlForOrder($this->order, $method);
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'refunded' => 'Refunded',
            default => ucfirst((string) ($status ?: 'pending')),
        };
    }
};
?>

<div class="payment-detail-page">
    <div class="payment-detail-shell">
        <section class="payment-detail-card">
            <p class="payment-kicker">Order berhasil dibuat</p>
            <h1>Detail Pembayaran</h1>

            @if(session('success'))
                <div class="payment-success-box">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="payment-error-box">
                    {{ session('error') }}
                </div>
            @endif

            <div class="payment-order-box">
                <div>
                    <span>Nomor Order</span>
                    <strong>{{ $order->order_number ?? ('#' . $order->id) }}</strong>
                </div>

                <div>
                    <span>Total Pembayaran</span>
                    <strong>{{ $this->formatRupiah($order->total_amount) }}</strong>
                </div>

                <div>
                    <span>Status</span>
                    <strong>{{ $this->statusLabel($order->payment_status) }}</strong>
                </div>
            </div>

            @php
                $method = $order->paymentMethod;
                $logo = data_get($method, 'logo');
                $qrImage = data_get($method, 'qr_image') ?? data_get($method, 'image');
                $paymentUrl = $this->paymentUrl();

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

                @if($this->isMidtrans())
                    @if($paymentUrl)
                        <div class="payment-midtrans-box">
                            <h2>Pembayaran Midtrans</h2>

                            <p>
                                Klik tombol di bawah untuk membuka halaman pembayaran Midtrans Sandbox.
                                Setelah pembayaran selesai, status order masih bisa dicek manual dari dashboard Midtrans sampai fitur cek status otomatis ditambahkan.
                            </p>

                            <a href="{{ $paymentUrl }}" target="_blank" class="payment-url-button">
                                Lanjutkan Pembayaran Midtrans
                            </a>
                        </div>
                    @else
                        <div class="payment-error-box">
                            Link pembayaran Midtrans belum tersedia. Kemungkinan koneksi ke Midtrans gagal saat checkout.
                            Silakan hubungi admin atau cek konfigurasi Midtrans.
                        </div>
                    @endif
                @elseif($paymentUrl)
                    <a href="{{ $paymentUrl }}" target="_blank" class="payment-url-button">
                        Buka Link Pembayaran
                    </a>
                @endif

                @if($this->whatsappUrl())
                    <div class="payment-whatsapp-box">
                        <h2>Konfirmasi via WhatsApp</h2>

                        <p>
                            Detail order sudah disiapkan otomatis. Klik tombol di bawah untuk membuka WhatsApp dan kirim pesan ke admin.
                        </p>

                        <a href="{{ $this->whatsappUrl() }}" target="_blank" class="payment-url-button payment-whatsapp-button">
                            Kirim Detail Order ke WhatsApp
                        </a>

                        <small>
                            Jika WhatsApp tidak terbuka otomatis, pastikan WhatsApp Web sudah login atau aplikasi WhatsApp tersedia di perangkat Anda.
                        </small>
                    </div>
                @endif

                @if($qrImage)
                    <div class="payment-qr-box">
                        <img src="{{ Storage::url($qrImage) }}" alt="QR Pembayaran">
                        <p>Scan QR setelah memastikan nominal pembayaran sudah benar.</p>
                    </div>
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
                @php
                    $snapshot = $item->snapshot_data ?? [];
                    $children = collect($snapshot['children'] ?? []);
                @endphp

                <div class="payment-summary-item payment-summary-item--rich">
                    <div>
                        <span>{{ $item->quantity }}x {{ $item->product_name ?? $item->product?->name }}</span>

                        @if($item->price_label)
                            <small class="payment-item-label">{{ $item->price_label }}</small>
                        @endif

                        @if((float) $item->discount_amount > 0)
                            <small class="payment-item-discount">
                                Hemat {{ $this->formatRupiah($item->discount_amount * $item->quantity) }}
                            </small>
                        @endif

                        @if($children->isNotEmpty())
                            <div class="payment-combo-children">
                                @foreach($children as $child)
                                    <small>
                                        {{ $child['total_quantity'] ?? $child['quantity_per_package'] ?? 1 }}x
                                        {{ $child['name'] ?? 'Produk paket' }}
                                    </small>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <strong>{{ $this->formatRupiah($item->total) }}</strong>
                </div>
            @endforeach

            <hr>

            <div class="payment-summary-item">
                <span>Subtotal</span>
                <strong>{{ $this->formatRupiah($order->subtotal) }}</strong>
            </div>

            @if((float) $order->discount_amount > 0)
                <div class="payment-summary-item">
                    <span>Total Diskon</span>
                    <strong>- {{ $this->formatRupiah($order->discount_amount) }}</strong>
                </div>
            @endif

            <div class="payment-summary-item">
                <span>Pengiriman</span>
                <strong>{{ $this->formatRupiah($order->shipping_cost) }}</strong>
            </div>

            <div class="payment-summary-total">
                <span>Total</span>
                <strong>{{ $this->formatRupiah($order->total_amount) }}</strong>
            </div>
        </aside>
    </div>
</div>