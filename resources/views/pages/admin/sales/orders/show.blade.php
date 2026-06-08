<?php

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\OrderInventoryService;
use App\Services\OrderPaymentStatusService;
use App\Support\MidtransPaymentChannel;
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
        if ($this->isMidtransOrder()) {
            $this->payment_status = $this->order->payment_status ?: 'pending';
        }

        $this->validate([
            'payment_status' => ['required', 'in:pending,paid,failed,expired,cancelled,refunded'],
            'order_status' => ['required', 'in:pending,processing,shipped,completed,cancelled'],
        ]);

        if (
            $this->isMidtransOrder()
            && $this->payment_status !== 'paid'
            && in_array($this->order_status, ['processing', 'shipped', 'completed'], true)
        ) {
            $this->addError('order_status', 'Order Midtrans sebaiknya diproses setelah payment_status sudah Lunas. Klik Cek Status Midtrans dulu.');
            return;
        }

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
            default => 'Belum Diproses',
        };
    }

    public function statusClass(?string $status): string
    {
        return match ($status) {
            'paid', 'completed' => 'admin-status-v2 admin-status-v2--active',
            'failed', 'expired', 'cancelled', 'refunded' => 'admin-status-v2 admin-status-v2--danger',
            'processing', 'shipped' => 'admin-status-v2 admin-status-v2--warning',
            default => 'admin-status-v2 admin-status-v2--inactive',
        };
    }

    public function isMidtransOrder(): bool
    {
        $method = $this->order->paymentMethod;

        return $this->order->payment_type === 'midtrans_snap'
            || ($method && $method->type === 'api' && strtolower((string) $method->api_provider) === 'midtrans');
    }

    public function midtransLastStatus(): string
    {
        $payload = $this->order->payment_notification_payload ?? [];
        $transactionStatus = data_get($payload, 'transaction_status');
        $fraudStatus = data_get($payload, 'fraud_status');

        if ($transactionStatus && $fraudStatus) {
            return $transactionStatus . ' / ' . $fraudStatus;
        }

        return $transactionStatus ?: '-';
    }

    public function paymentTypeLabel(): string
    {
        if ($this->isMidtransOrder()) {
            return 'Midtrans Snap';
        }

        return ucfirst((string) ($this->order->payment_type ?: 'manual'));
    }

    public function paymentChannelLabel(): string
    {
        if ($this->isMidtransOrder()) {
            $label = $this->order->payment_channel_label
                ?: MidtransPaymentChannel::label($this->order->payment_channel ?: $this->order->paymentMethod?->midtrans_channel_code);

            return trim($label . ' via Midtrans');
        }

        return $this->order->paymentMethod?->name ?: ucfirst((string) ($this->order->payment_type ?: 'Manual'));
    }

    public function midtransActualPaymentLabel(): string
    {
        $payload = $this->order->payment_notification_payload ?? [];
        $actual = MidtransPaymentChannel::actualCodeFromPayload((array) $payload);

        if ($actual) {
            return MidtransPaymentChannel::label($actual);
        }

        if ($this->order->midtrans_payment_type) {
            return MidtransPaymentChannel::label($this->order->midtrans_payment_type);
        }

        return '-';
    }

    public function midtransVaDisplay(): string
    {
        return $this->order->midtrans_va_number ?: data_get($this->order->payment_notification_payload ?? [], 'va_numbers.0.va_number') ?: '-';
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

    public function orderItemCount(): int
    {
        return (int) $this->order->items->sum('quantity');
    }

    public function orderProgressIndex(): int
    {
        return match ($this->order->order_status) {
            'processing' => 2,
            'shipped' => 3,
            'completed' => 4,
            'cancelled' => 0,
            default => 1,
        };
    }

    public function shippingAddressLines(): array
    {
        return array_values(array_filter([
            $this->order->shipping_address,
            trim(collect([
                $this->order->shipping_district,
                $this->order->shipping_city,
                $this->order->shipping_province,
                $this->order->shipping_postal_code,
            ])->filter()->implode(', ')),
        ]));
    }
};
?>

<div class="admin-page-v2 admin-order-page-v2 admin-order-show-v3 admin-order-show-compact-v4">
    @php
        $payload = $order->payment_notification_payload ?? [];
        $progress = $this->orderProgressIndex();
        $createdLabel = $order->created_at?->format('d M Y, H:i');
        $updatedLabel = $order->updated_at?->format('d M Y, H:i');
    @endphp

    <div class="admin-order-hero-v3">
        <div class="admin-order-hero-v3__main">
            <a href="{{ route('admin.sales.orders') }}" class="admin-back-link-v2" wire:navigate>
                ← Kembali ke Orders
            </a>

            <div class="admin-order-hero-v3__top">
                <div>
                    <p class="admin-order-hero-v3__eyebrow">Detail pesanan</p>
                    <h2>{{ $order->order_number ?? '#' . $order->id }}</h2>
                    <p class="admin-order-hero-v3__subtitle">
                        {{ $createdLabel }} · Update {{ $updatedLabel }}
                    </p>
                </div>

                <div class="admin-order-hero-v3__badges">
                    <span class="{{ $this->statusClass($order->payment_status) }}">
                        {{ $this->paymentStatusLabel($order->payment_status) }}
                    </span>

                    <span class="{{ $this->statusClass($order->order_status) }}">
                        {{ $this->orderStatusLabel($order->order_status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="admin-order-meta-cards-v3">
            <article>
                <span>Total Order</span>
                <strong>{{ $this->formatRupiah($order->total_amount) }}</strong>
                <small>{{ $this->orderItemCount() }} item</small>
            </article>
            <article>
                <span>Customer</span>
                <strong>{{ $order->customer_name ?: ($order->user?->name ?? '-') }}</strong>
                <small>{{ $order->customer_phone ?: '-' }}</small>
            </article>
            <article>
                <span>Pembayaran</span>
                <strong>{{ $this->paymentChannelLabel() }}</strong>
                <small>{{ $this->paymentTypeLabel() }}</small>
            </article>
            <article>
                <span>Pengiriman</span>
                <strong>{{ $order->shippingMethod?->name ?? '-' }}</strong>
                <small>{{ $order->shipping_city ?: '-' }}</small>
            </article>
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

    <div class="admin-order-progress-v3 {{ $order->order_status === 'cancelled' ? 'is-cancelled' : '' }}">
        <div class="admin-order-progress-v3__line"></div>

        @foreach([
            1 => ['label' => 'Baru', 'desc' => 'Pesanan masuk'],
            2 => ['label' => 'Diproses', 'desc' => 'Siap dikemas'],
            3 => ['label' => 'Dikirim', 'desc' => 'Sedang dikirim'],
            4 => ['label' => 'Selesai', 'desc' => 'Pesanan selesai'],
        ] as $step => $meta)
            <div class="admin-order-progress-v3__step {{ $progress >= $step ? 'is-active' : '' }}">
                <span>{{ $step }}</span>
                <strong>{{ $meta['label'] }}</strong>
                <small>{{ $meta['desc'] }}</small>
            </div>
        @endforeach
    </div>

    <div class="admin-order-layout-v3">
        <div class="admin-order-main-v3">
            <section class="admin-panel-v2 admin-order-card-v3">
                <div class="admin-order-card-v3__head">
                    <div>
                        <h3>Ringkasan Order</h3>
                        <p>Customer, pembayaran, dan pengiriman.</p>
                    </div>
                </div>

                <div class="admin-order-info-grid-v3">
                    <div class="admin-order-info-block-v3">
                        <h4>Informasi Customer</h4>
                        <dl>
                            <div><dt>Nama</dt><dd>{{ $order->customer_name ?: '-' }}</dd></div>
                            <div><dt>Email</dt><dd>{{ $order->customer_email ?: '-' }}</dd></div>
                            <div><dt>Phone</dt><dd>{{ $order->customer_phone ?: '-' }}</dd></div>
                            <div><dt>Akun</dt><dd>{{ $order->user?->name ? $order->user->name . ' · ID #' . $order->user->id : 'Guest / tidak terhubung' }}</dd></div>
                        </dl>
                    </div>

                    <div class="admin-order-info-block-v3">
                        <h4>Informasi Order</h4>
                        <dl>
                            <div><dt>Nomor Order</dt><dd>{{ $order->order_number ?: '#' . $order->id }}</dd></div>
                            <div><dt>Dibuat</dt><dd>{{ $createdLabel ?: '-' }}</dd></div>
                            <div><dt>Last Update</dt><dd>{{ $updatedLabel ?: '-' }}</dd></div>
                            <div><dt>Jumlah Item</dt><dd>{{ $this->orderItemCount() }} item</dd></div>
                        </dl>
                    </div>

                    <div class="admin-order-info-block-v3">
                        <h4>Pembayaran</h4>
                        <dl>
                            <div><dt>Metode</dt><dd>{{ $this->paymentChannelLabel() }}</dd></div>
                            <div><dt>Gateway</dt><dd>{{ $this->paymentTypeLabel() }}</dd></div>
                            <div><dt>Status</dt><dd>{{ $this->paymentStatusLabel($order->payment_status) }}</dd></div>
                            <div><dt>Reference</dt><dd>{{ $order->payment_reference ?: '-' }}</dd></div>
                            <div><dt>Paid At</dt><dd>{{ $order->paid_at?->format('d M Y, H:i') ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    <div class="admin-order-info-block-v3">
                        <h4>Pengiriman</h4>
                        <dl>
                            <div><dt>Kurir</dt><dd>{{ $order->shippingMethod?->name ?? '-' }}</dd></div>
                            <div><dt>Status Order</dt><dd>{{ $this->orderStatusLabel($order->order_status) }}</dd></div>
                            <div><dt>Kota</dt><dd>{{ $order->shipping_city ?: '-' }}</dd></div>
                            <div><dt>Provinsi</dt><dd>{{ $order->shipping_province ?: '-' }}</dd></div>
                        </dl>
                    </div>
                </div>
            </section>

            <section class="admin-panel-v2 admin-order-card-v3">
                <div class="admin-order-card-v3__head">
                    <div>
                        <h3>Alamat Pengiriman</h3>
                        <p>Alamat saat checkout.</p>
                    </div>
                </div>

                <div class="admin-order-address-v3">
                    <div class="admin-order-address-v3__badge">SHIP TO</div>
                    <strong>{{ $order->customer_name ?: '-' }}</strong>
                    <span>{{ $order->customer_phone ?: '-' }}</span>

                    @foreach($this->shippingAddressLines() as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            </section>

            @if($this->isMidtransOrder())
                <section class="admin-panel-v2 admin-order-card-v3">
                    <div class="admin-order-card-v3__head">
                        <div>
                            <h3>Midtrans Insight</h3>
                            <p>Data webhook/sync terakhir.</p>
                        </div>
                    </div>

                    <div class="admin-order-midtrans-grid-v3">
                        <div class="admin-midtrans-box-v2">
                            <strong>Status terakhir</strong>
                            <span>{{ $this->midtransLastStatus() }}</span>
                        </div>
                        <div class="admin-midtrans-box-v2">
                            <strong>Transaction ID</strong>
                            <span>{{ data_get($payload, 'transaction_id', '-') }}</span>
                        </div>
                        <div class="admin-midtrans-box-v2">
                            <strong>Channel Aktual</strong>
                            <span>{{ $this->midtransActualPaymentLabel() }}</span>
                        </div>
                        <div class="admin-midtrans-box-v2">
                            <strong>VA / Kode Bayar</strong>
                            <span>{{ $this->midtransVaDisplay() }}</span>
                        </div>
                        <div class="admin-midtrans-box-v2">
                            <strong>Gross Amount</strong>
                            <span>{{ data_get($payload, 'gross_amount', '-') }}</span>
                        </div>
                    </div>
                </section>
            @endif

            <section class="admin-panel-v2 admin-order-card-v3 admin-order-items-panel-v2">
                <div class="admin-order-card-v3__head">
                    <div>
                        <h3>Item Order</h3>
                        <p>Item dan total harga.</p>
                    </div>
                </div>

                <div class="admin-order-items-v2">
                    @foreach($order->items as $item)
                        @php
                            $snapshot = $item->snapshot_data ?? [];
                            $children = collect($snapshot['children'] ?? []);
                            $image = $this->imageUrl($item->product_image);
                        @endphp

                        <article class="admin-order-item-v2 admin-order-item-v3">
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

                                <div class="admin-order-item-price-v2 admin-order-item-price-v3">
                                    <div>
                                        <span>Harga / item</span>
                                        <strong>{{ $this->formatRupiah($item->price) }}</strong>
                                    </div>

                                    @if((float) $item->discount_amount > 0)
                                        <div>
                                            <span>Diskon / item</span>
                                            <strong>- {{ $this->formatRupiah($item->discount_amount) }}</strong>
                                        </div>
                                    @endif

                                    <div>
                                        <span>Qty</span>
                                        <strong>{{ $item->quantity }}</strong>
                                    </div>

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

            <section class="admin-panel-v2 admin-order-card-v3 admin-order-logs-v2">
                <div class="admin-order-card-v3__head">
                    <div>
                        <h3>Riwayat Status</h3>
                        <p>Perubahan status order.</p>
                    </div>
                </div>

                <div class="admin-order-log-list-v3">
                    @forelse($order->statusLogs->sortByDesc('created_at') as $log)
                        <div class="admin-order-log-row-v2 admin-order-log-row-v3">
                            <div>
                                <strong>{{ ucfirst($log->source) }}</strong>
                                <small>{{ $log->created_at?->format('d M Y H:i') }} · {{ $log->user?->name ?? 'Sistem' }}</small>
                            </div>
                            <p>
                                Pembayaran: {{ $log->old_payment_status ?: '-' }} → {{ $log->new_payment_status ?: '-' }}<br>
                                Order: {{ $log->old_order_status ?: '-' }} → {{ $log->new_order_status ?: '-' }}
                                @if($log->note)
                                    <br><span>{{ $log->note }}</span>
                                @endif
                            </p>
                        </div>
                    @empty
                        <p class="admin-empty-v3">Belum ada riwayat perubahan status.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="admin-order-sidebar-v3">
            <section class="admin-panel-v2 admin-order-card-v3 admin-order-sticky-v3">
                <div class="admin-order-card-v3__head">
                    <div>
                        <h3>Aksi Admin</h3>
                        <p>Update progress order.</p>
                    </div>
                </div>

                @if($this->isMidtransOrder())
                    <div class="admin-midtrans-box-v2">
                        <strong>Otomatis via Midtrans</strong>
                        <span>Status pembayaran diambil dari webhook / cek status. Admin fokus pada progress order.</span>
                    </div>
                @endif

                <form wire:submit="saveStatuses" class="admin-form-v2 admin-form-v3">
                    <label>
                        <span>Status Pembayaran</span>
                        <select wire:model="payment_status" @disabled($this->isMidtransOrder())>
                            <option value="pending">Pending</option>
                            <option value="paid">Lunas</option>
                            <option value="failed">Gagal</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Dibatalkan</option>
                            <option value="refunded">Refund</option>
                        </select>

                        @if($this->isMidtransOrder())
                            <small class="admin-form-note-v2">
                                Order Midtrans: status pembayaran tidak diubah manual agar data tetap sinkron.
                            </small>
                        @endif
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

                        @error('order_status')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="admin-actions-v2 admin-actions-v3">
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

            <section class="admin-panel-v2 admin-order-card-v3 admin-order-summary-v3">
                <div class="admin-order-card-v3__head">
                    <div>
                        <h3>Ringkasan Pembayaran</h3>
                        <p>Nominal pembayaran.</p>
                    </div>
                </div>

                <div class="admin-order-summary-v2">
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
                </div>
            </section>
        </aside>
    </div>
</div>
