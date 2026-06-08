<?php

use App\Models\Order;
use App\Services\MidtransPaymentService;
use App\Services\OrderPaymentStatusService;
use App\Support\MidtransPaymentChannel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Orders - Admin Compify')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $payment_status = '';
    public string $order_status = '';
    public string $workflow_status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function updatedOrderStatus(): void
    {
        $this->resetPage();
    }

    public function setWorkflowStatus(string $status): void
    {
        if (! in_array($status, ['all', 'new', 'processing', 'completed', 'cancelled'], true)) {
            return;
        }

        $this->workflow_status = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->payment_status = '';
        $this->order_status = '';
        $this->workflow_status = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all' => Order::query()->count(),
            'new' => Order::query()->where('order_status', 'pending')->count(),
            'processing' => Order::query()->whereIn('order_status', ['processing', 'shipped'])->count(),
            'completed' => Order::query()->where('order_status', 'completed')->count(),
            'cancelled' => Order::query()
                ->where(function ($query) {
                    $query->where('order_status', 'cancelled')
                        ->orWhereIn('payment_status', ['failed', 'expired', 'cancelled', 'refunded']);
                })
                ->count(),
            'pending_midtrans' => Order::query()
                ->where('payment_type', 'midtrans_snap')
                ->where('payment_status', 'pending')
                ->count(),
        ];
    }

    #[Computed]
    public function orders()
    {
        $search = trim($this->search);

        return Order::query()
            ->with(['paymentMethod'])
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('order_number', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('customer_email', 'like', $like)
                        ->orWhere('customer_phone', 'like', $like);
                });
            })
            ->when($this->payment_status !== '', function ($query) {
                $query->where('payment_status', $this->payment_status);
            })
            ->when($this->order_status !== '', function ($query) {
                $query->where('order_status', $this->order_status);
            })
            ->when($this->workflow_status !== 'all', function ($query) {
                $this->applyWorkflowScope($query, $this->workflow_status);
            })
            ->latest()
            ->paginate(12);
    }

    private function applyWorkflowScope($query, string $status): void
    {
        match ($status) {
            'new' => $query->where('order_status', 'pending'),
            'processing' => $query->whereIn('order_status', ['processing', 'shipped']),
            'completed' => $query->where('order_status', 'completed'),
            'cancelled' => $query->where(function ($q) {
                $q->where('order_status', 'cancelled')
                    ->orWhereIn('payment_status', ['failed', 'expired', 'cancelled', 'refunded']);
            }),
            default => null,
        };
    }

    public function syncPendingMidtrans(): void
    {
        $orders = Order::query()
            ->where('payment_type', 'midtrans_snap')
            ->where('payment_status', 'pending')
            ->latest()
            ->limit(20)
            ->get();

        if ($orders->isEmpty()) {
            session()->flash('success', 'Tidak ada order Midtrans pending yang perlu dicek.');
            return;
        }

        $total = $orders->count();
        $checked = 0;
        $updated = 0;
        $failedOrders = [];
        $notFoundOrders = [];
        $firstError = null;

        $midtrans = app(MidtransPaymentService::class);
        $paymentStatusService = app(OrderPaymentStatusService::class);

        foreach ($orders as $order) {
            $orderNumber = $order->order_number ?: '#' . $order->id;

            try {
                $before = $order->payment_status . '|' . $order->order_status;
                $payload = $midtrans->getStatus($order);
                $checked++;

                $freshOrder = $paymentStatusService->applyMidtransPayload($order, $payload, 'admin_midtrans_sync');
                $after = $freshOrder->payment_status . '|' . $freshOrder->order_status;

                if ($before !== $after) {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $isNotFound = str_contains($message, 'Transaction doesn\'t exist')
                    || str_contains($message, '"status_code":"404"')
                    || str_contains($message, 'HTTP status code: 404');

                if ($isNotFound) {
                    $notFoundOrders[] = $orderNumber;

                    logger()->info('Order Midtrans pending tidak ditemukan di Midtrans saat sync admin.', [
                        'order_id' => $order->id,
                        'order_number' => $orderNumber,
                        'message' => $message,
                    ]);

                    continue;
                }

                report($e);

                $failedOrders[] = $orderNumber;
                $firstError ??= $message;

                logger()->warning('Admin gagal sync status Midtrans.', [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'message' => $message,
                ]);
            }
        }

        $failed = count($failedOrders);
        $notFound = count($notFoundOrders);
        $message = "Sync Midtrans selesai. Pending ditemukan: {$total}. Berhasil dicek: {$checked}. Berubah: {$updated}. Tidak ada di Midtrans: {$notFound}. Gagal sistem: {$failed}.";

        if ($notFound > 0) {
            $message .= ' Order tidak ada di Midtrans: ' . implode(', ', array_slice($notFoundOrders, 0, 5)) . '.';
            $message .= ' Biasanya ini order testing lama, order yang belum berhasil dibuatkan Snap, atau key/environment Midtrans tidak cocok.';
        }

        if ($failed > 0) {
            $message .= ' Order gagal sistem: ' . implode(', ', array_slice($failedOrders, 0, 5)) . '.';

            if (config('app.debug') && $firstError) {
                $message .= ' Detail pertama: ' . mb_substr($firstError, 0, 180);
            } else {
                $message .= ' Cek storage/logs/laravel.log untuk detail.';
            }
        }

        session()->flash($failed > 0 ? 'error' : ($notFound > 0 ? 'warning' : 'success'), $message);
        $this->resetPage();
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

    public function paymentMethodLabel(Order $order): string
    {
        if (
            $order->payment_gateway === 'midtrans'
            || $order->payment_type === 'midtrans_snap'
            || ($order->paymentMethod && $order->paymentMethod->type === 'api' && strtolower((string) $order->paymentMethod->api_provider) === 'midtrans')
        ) {
            $label = $order->payment_channel_label
                ?: MidtransPaymentChannel::label($order->payment_channel ?: $order->paymentMethod?->midtrans_channel_code);

            return trim($label . ' via Midtrans');
        }

        return $order->paymentMethod?->name ?: ucfirst((string) ($order->payment_type ?: 'Manual'));
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

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
};
?>

<div class="admin-page-v2 admin-order-page-v2 admin-order-index-compact-v4">
    <div class="admin-section-title-v2">
        <div>
            <h2>Orders</h2>
            <p>Pantau status bayar Midtrans dan proses pesanan dengan cepat.</p>
        </div>

        <div class="admin-actions-v2">
            <button
                type="button"
                class="admin-btn-v2 admin-btn-v2--primary"
                wire:click="syncPendingMidtrans"
                wire:loading.attr="disabled"
                wire:target="syncPendingMidtrans"
            >
                <span wire:loading.remove wire:target="syncPendingMidtrans">Sync Midtrans</span>
                <span wire:loading wire:target="syncPendingMidtrans">Mengecek...</span>
            </button>
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

    @if(session('warning'))
        <div class="admin-alert-v2 admin-alert-v2--warning">
            {{ session('warning') }}
        </div>
    @endif

    <div class="admin-order-stats-v2">
        <div>
            <span>Semua</span>
            <strong>{{ $this->counts['all'] }}</strong>
        </div>
        <div>
            <span>Baru</span>
            <strong>{{ $this->counts['new'] }}</strong>
        </div>
        <div>
            <span>Diproses</span>
            <strong>{{ $this->counts['processing'] }}</strong>
        </div>
        <div>
            <span>Selesai</span>
            <strong>{{ $this->counts['completed'] }}</strong>
        </div>
        <div>
            <span>Midtrans Pending</span>
            <strong>{{ $this->counts['pending_midtrans'] }}</strong>
        </div>
    </div>

    <div class="admin-tabs-v2 admin-order-tabs-v2">
        <button type="button" @class(['admin-tab-v2', 'active' => $workflow_status === 'all']) wire:click="setWorkflowStatus('all')">
            Semua {{ $this->counts['all'] }}
        </button>
        <button type="button" @class(['admin-tab-v2', 'active' => $workflow_status === 'new']) wire:click="setWorkflowStatus('new')">
            Baru {{ $this->counts['new'] }}
        </button>
        <button type="button" @class(['admin-tab-v2', 'active' => $workflow_status === 'processing']) wire:click="setWorkflowStatus('processing')">
            Diproses {{ $this->counts['processing'] }}
        </button>
        <button type="button" @class(['admin-tab-v2', 'active' => $workflow_status === 'completed']) wire:click="setWorkflowStatus('completed')">
            Selesai {{ $this->counts['completed'] }}
        </button>
        <button type="button" @class(['admin-tab-v2', 'active' => $workflow_status === 'cancelled']) wire:click="setWorkflowStatus('cancelled')">
            Batal/Gagal {{ $this->counts['cancelled'] }}
        </button>
    </div>

    <div class="admin-panel-v2 admin-form-v2">
        <div class="admin-grid-v2 admin-grid-v2--order-filter">
            <label>
                <span>Cari Order</span>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari order, nama, email, phone"
                >
            </label>

            <label>
                <span>Pembayaran</span>
                <select wire:model.live="payment_status">
                    <option value="">Semua</option>
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
                <select wire:model.live="order_status">
                    <option value="">Semua</option>
                    <option value="pending">Belum Diproses</option>
                    <option value="processing">Diproses</option>
                    <option value="shipped">Dikirim</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </label>

            <div class="admin-order-filter-action-v2">
                <button type="button" class="admin-btn-v2" wire:click="resetFilters">
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    <div class="admin-panel-v2 admin-table-wrap-v2">
        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Total</th>
                    <th>Metode</th>
                    <th>Pembayaran</th>
                    <th>Status Order</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->order_number ?? '#' . $order->id }}</strong>
                            @if($order->payment_type === 'midtrans_snap' || $order->payment_gateway === 'midtrans')
                                <small>{{ $order->payment_channel_label ?: 'Midtrans' }}</small>
                            @endif
                        </td>

                        <td>
                            <strong>{{ $order->customer_name }}</strong>
                            <small>{{ $order->customer_email }}</small>
                        </td>

                        <td>{{ $order->items_count }} item</td>

                        <td>
                            <strong>{{ $this->formatRupiah($order->total_amount) }}</strong>
                        </td>

                        <td>
                            <strong>{{ $this->paymentMethodLabel($order) }}</strong>
                            @if($order->paid_at)
                                <small>Paid: {{ $order->paid_at?->format('d M Y H:i') }}</small>
                            @endif
                        </td>

                        <td>
                            <span class="{{ $this->statusClass($order->payment_status) }}">
                                {{ $this->paymentStatusLabel($order->payment_status) }}
                            </span>
                        </td>

                        <td>
                            <span class="{{ $this->statusClass($order->order_status) }}">
                                {{ $this->orderStatusLabel($order->order_status) }}
                            </span>
                        </td>

                        <td>{{ $order->created_at?->format('d M Y H:i') }}</td>

                        <td>
                            <a
                                href="{{ route('admin.sales.orders.show', $order) }}"
                                class="admin-btn-v2 admin-btn-v2--sm"
                                wire:navigate
                            >
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">Belum ada order sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination-v2">
            {{ $this->orders->links() }}
        </div>
    </div>
</div>
