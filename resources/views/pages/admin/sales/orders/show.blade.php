<?php

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\WhatsAppOrderMessageService;
use App\Services\OrderInventoryService;
use App\Services\OrderPaymentStatusService;
use Illuminate\Support\Facades\DB;

new
#[Layout('components.layouts.admin')]
#[Title('Detail Order - Admin Compify')]
class extends Component
{
    public Order $order;

    public string $payment_status = 'pending';
    public string $order_status = 'pending';

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'items.product',
            'items.comboPackage',
            'items.flashSaleItem',
            'paymentMethod',
            'shippingMethod',
            'user',
            'statusLogs.user',
        ]);

        $this->payment_status = $this->order->payment_status ?: 'pending';
        $this->order_status = $this->order->order_status ?: 'pending';
    }

    public function saveStatuses(): void
    {
        $this->validate([
            'payment_status' => ['required', 'in:pending,paid,failed,expired,cancelled,refunded'],
            'order_status' => ['required', 'in:pending,processing,shipped,completed,cancelled'],
        ]);

        $oldPaymentStatus = $this->order->payment_status;
        $oldOrderStatus = $this->order->order_status;

        $paymentService = app(OrderPaymentStatusService::class);

        if ($this->payment_status === 'paid') {
            $this->order = $paymentService->markPaid($this->order);
        } elseif (in_array($this->payment_status, ['failed', 'expired', 'cancelled', 'refunded'], true)) {
            $this->order = $paymentService->markFailedOrExpired($this->order, $this->payment_status);
        } elseif ($this->order_status === 'cancelled') {
            $this->order = $paymentService->markFailedOrExpired($this->order, 'cancelled');
        } else {
            $this->order->update(['payment_status' => $this->payment_status]);
            $this->order->refresh();
        }

        $finalOrderStatus = in_array($this->payment_status, ['expired', 'cancelled'], true)
            ? 'cancelled'
            : $this->order_status;

        $this->order->update(['order_status' => $finalOrderStatus]);
        $this->order->refresh();

        $this->order_status = $this->order->order_status;
        $this->payment_status = $this->order->payment_status;

        if ($oldPaymentStatus !== $this->order->payment_status || $oldOrderStatus !== $this->order->order_status) {
            OrderStatusLog::create([
                'order_id' => $this->order->id,
                'user_id' => auth('admin')->id(),
                'source' => 'admin',
                'old_payment_status' => $oldPaymentStatus,
                'new_payment_status' => $this->order->payment_status,
                'old_order_status' => $oldOrderStatus,
                'new_order_status' => $this->order->order_status,
                'note' => 'Status diubah dari halaman admin.',
            ]);
        }

        $this->order->load('statusLogs.user');

        session()->flash('success', 'Status order berhasil diperbarui.');
    }

    public function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'expired' => 'Expired',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Refund',
            default => 'Pending',
        };
    }

    public function orderStatusLabel(?string $status): string
    {
        return match ($status) {
            'processing' => 'Diproses',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => 'Pending',
        };
    }

    public function statusClass(?string $status): string
    {
        return match ($status) {
            'paid', 'completed' => 'admin-status-v2 admin-status-v2--active',
            'failed', 'expired', 'cancelled' => 'admin-status-v2 admin-status-v2--danger',
            'processing', 'shipped' => 'admin-status-v2 admin-status-v2--warning',
            default => 'admin-status-v2 admin-status-v2--inactive',
        };
    }

    public function itemTypeLabel($item): string
    {
        return match ($item->item_type) {
            'event_flash_sale' => 'Flash Sale',
            'combo_package' => 'Paket Bundling',
            default => 'Produk',
        };
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public function customerWhatsappUrl(): ?string
    {
        return app(WhatsAppOrderMessageService::class)
            ->customerUrlForOrder($this->order);
    }

    public function canDeleteOrder(): bool
    {
        return in_array($this->order->payment_status, ['pending', 'failed', 'expired'], true)
            || in_array($this->order->order_status, ['pending', 'cancelled'], true);
    }

    public function deleteOrder()
    {
        if (! $this->canDeleteOrder()) {
            session()->flash('error', 'Order yang sudah dibayar/diproses tidak bisa dihapus langsung. Ubah status atau batalkan order terlebih dahulu.');
            return;
        }

        DB::transaction(function () {
            app(OrderInventoryService::class)->restore($this->order);
            $this->order->delete();
        });

        session()->flash('success', 'Order berhasil dihapus dan stok dikembalikan.');

        return $this->redirectRoute('admin.sales.orders', navigate: true);
    }
};
?>

<div class="admin-page-v2 admin-order-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <a href="{{ route('admin.sales.orders') }}" class="admin-back-link-v2" wire:navigate>
                ← Kembali ke Orders
            </a>

            <h2>Detail Order</h2>
            <p>{{ $order->order_number ?? '#' . $order->id }}</p>
        </div>

        <div class="admin-order-title-actions-v2">
            <span class="{{ $this->statusClass($order->payment_status) }}">
                {{ $this->paymentStatusLabel($order->payment_status) }}
            </span>

            <span class="{{ $this->statusClass($order->order_status) }}">
                {{ $this->orderStatusLabel($order->order_status) }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="admin-alert-v2 admin-alert-v2--danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="admin-order-detail-grid-v2">
        <section class="admin-panel-v2">
            <h3>Data Customer</h3>

            <div class="admin-info-list-v2">
                <div>
                    <span>Nama</span>
                    <strong>{{ $order->customer_name }}</strong>
                </div>

                <div>
                    <span>Email</span>
                    <strong>{{ $order->customer_email }}</strong>
                </div>

                <div>
                    <span>Phone</span>
                    <strong>{{ $order->customer_phone }}</strong>
                </div>

                <div>
                    <span>Tanggal Order</span>
                    <strong>{{ $order->created_at?->format('d M Y H:i') }}</strong>
                </div>
            </div>
        </section>

        <section class="admin-panel-v2">
            <h3>Alamat Pengiriman</h3>

            <div class="admin-address-box-v2">
                <strong>{{ $order->shipping_address }}</strong>
                <span>
                    {{ $order->shipping_district }},
                    {{ $order->shipping_city }},
                    {{ $order->shipping_province }}
                    {{ $order->shipping_postal_code }}
                </span>
            </div>

            <div class="admin-info-list-v2 admin-info-list-v2--compact">
                <div>
                    <span>Shipping</span>
                    <strong>{{ $order->shippingMethod?->name ?? '-' }}</strong>
                </div>

                <div>
                    <span>Payment</span>
                    <strong>{{ $order->paymentMethod?->name ?? '-' }}</strong>
                </div>
            </div>

            @if($this->customerWhatsappUrl())
                <div class="admin-order-wa-actions-v2">
                    <a
                        href="{{ $this->customerWhatsappUrl() }}"
                        target="_blank"
                        class="admin-btn-v2 admin-btn-v2--whatsapp"
                    >
                        Hubungi Customer via WhatsApp
                    </a>

                    <small>
                        Membuka WhatsApp dengan pesan status order otomatis.
                    </small>
                </div>
            @endif
        </section>

        <section class="admin-panel-v2">
            <h3>Update Status</h3>

            <form wire:submit="saveStatuses" class="admin-form-v2">
                <label>
                    <span>Status Pembayaran</span>
                    <select wire:model="payment_status">
                        <option value="pending">Pending</option>
                        <option value="paid">Lunas</option>
                        <option value="failed">Gagal</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Dibatalkan</option>
                        <option value="refunded">Refund</option>
                    </select>
                </label>

                <label>
                    <span>Status Order</span>
                    <select wire:model="order_status">
                        <option value="pending">Pending</option>
                        <option value="processing">Diproses</option>
                        <option value="shipped">Dikirim</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </label>

                <div class="admin-actions-v2">
                    <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                        Simpan Status
                    </button>

                    @if($order->payment_type === 'midtrans_snap')
                        <button type="submit" form="admin-check-midtrans-form" class="admin-btn-v2">
                            Cek Status Midtrans
                        </button>
                    @endif

                    @if($this->canDeleteOrder())
                        <button
                            type="button"
                            class="admin-btn-v2 admin-btn-v2--danger"
                            wire:click="deleteOrder"
                            wire:confirm="Yakin hapus order ini? Stok produk akan dikembalikan dan data order akan hilang."
                        >
                            Hapus Order
                        </button>
                    @endif
                </div>

            </form>

            @if($order->payment_type === 'midtrans_snap')
                <form id="admin-check-midtrans-form" method="POST" action="{{ route('admin.sales.orders.check-payment-status', $order) }}">
                    @csrf
                </form>
            @endif
        </section>
    </div>

    <section class="admin-panel-v2 admin-order-items-panel-v2">
        <h3>Item Order</h3>

        <div class="admin-order-items-v2">
            @foreach($order->items as $item)
                @php
                    $snapshot = $item->snapshot_data ?? [];
                    $children = collect($snapshot['children'] ?? []);
                    $image = $this->imageUrl($item->product_image);
                @endphp

                <article class="admin-order-item-v2">
                    <div class="admin-order-item-v2__image">
                        @if($image)
                            <img src="{{ $image }}" alt="{{ $item->product_name }}">
                        @else
                            <span>{{ strtoupper(substr($item->product_name ?? 'IT', 0, 2)) }}</span>
                        @endif
                    </div>

                    <div class="admin-order-item-v2__content">
                        <div class="admin-order-item-v2__head">
                            <div>
                                <strong>{{ $item->product_name }}</strong>
                                <span>{{ $item->quantity }}x {{ $this->formatRupiah($item->price) }}</span>
                            </div>

                            <span class="{{ $item->item_type === 'event_flash_sale' ? 'admin-order-type-v2 admin-order-type-v2--event' : ($item->item_type === 'combo_package' ? 'admin-order-type-v2 admin-order-type-v2--combo' : 'admin-order-type-v2') }}">
                                {{ $this->itemTypeLabel($item) }}
                            </span>
                        </div>

                        <div class="admin-order-item-price-v2">
                            @if((float) $item->discount_amount > 0)
                                <div>
                                    <span>Harga awal</span>
                                    <del>{{ $this->formatRupiah($item->original_price) }}</del>
                                </div>

                                <div>
                                    <span>Diskon / item</span>
                                    <strong>- {{ $this->formatRupiah($item->discount_amount) }}</strong>
                                </div>
                            @endif

                            <div>
                                <span>Total</span>
                                <strong>{{ $this->formatRupiah($item->total) }}</strong>
                            </div>
                        </div>

                        @if($item->price_label)
                            <small class="admin-order-note-v2">
                                Label harga: {{ $item->price_label }}
                            </small>
                        @endif

                        @if($children->isNotEmpty())
                            <div class="admin-order-combo-children-v2">
                                <strong>Isi Paket</strong>

                                @foreach($children as $child)
                                    <div>
                                        <span>
                                            {{ $child['total_quantity'] ?? $child['quantity_per_package'] ?? 1 }}x
                                            {{ $child['name'] ?? 'Produk paket' }}
                                        </span>

                                        <small>
                                            {{ $this->formatRupiah($child['line_total_all_package'] ?? $child['line_total_per_package'] ?? 0) }}
                                        </small>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="admin-panel-v2 admin-order-logs-v2">
        <h3>Riwayat Status</h3>

        @forelse($order->statusLogs->sortByDesc('created_at') as $log)
            <div class="admin-order-log-row-v2">
                <div>
                    <strong>{{ ucfirst($log->source) }}</strong>
                    <small>{{ $log->created_at?->format('d M Y H:i') }} oleh {{ $log->user?->name ?? 'Sistem' }}</small>
                </div>
                <p>
                    Pembayaran: {{ $log->old_payment_status ?: '-' }} → {{ $log->new_payment_status ?: '-' }}<br>
                    Order: {{ $log->old_order_status ?: '-' }} → {{ $log->new_order_status ?: '-' }}
                </p>
            </div>
        @empty
            <p>Belum ada riwayat perubahan status.</p>
        @endforelse
    </section>

    <section class="admin-panel-v2 admin-order-summary-v2">
        <h3>Ringkasan Pembayaran</h3>

        <div>
            <span>Subtotal</span>
            <strong>{{ $this->formatRupiah($order->subtotal) }}</strong>
        </div>

        @if((float) $order->discount_amount > 0)
            <div>
                <span>Total Diskon</span>
                <strong>- {{ $this->formatRupiah($order->discount_amount) }}</strong>
            </div>
        @endif

        <div>
            <span>Ongkir</span>
            <strong>{{ $this->formatRupiah($order->shipping_cost) }}</strong>
        </div>

        <div class="is-total">
            <span>Total Order</span>
            <strong>{{ $this->formatRupiah($order->total_amount) }}</strong>
        </div>
    </section>
</div>