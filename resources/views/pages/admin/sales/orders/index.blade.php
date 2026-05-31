<?php

use App\Models\Order;
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

    #[Computed]
    public function orders()
    {
        $search = trim($this->search);

        return Order::query()
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
            ->latest()
            ->paginate(12);
    }

    public function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'expired' => 'Expired',
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

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
};
?>

<div class="admin-page-v2 admin-order-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Orders</h2>
            <p>Kelola pesanan customer, status pembayaran, dan status pengiriman.</p>
        </div>
    </div>

    <div class="admin-panel-v2 admin-form-v2">
        <div class="admin-grid-v2 admin-grid-v2--order-filter">
            <label>
                <span>Cari Order</span>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari nomor order, nama, email, atau phone"
                >
            </label>

            <label>
                <span>Status Pembayaran</span>
                <select wire:model.live="payment_status">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Lunas</option>
                    <option value="failed">Gagal</option>
                    <option value="expired">Expired</option>
                    <option value="refunded">Refund</option>
                </select>
            </label>

            <label>
                <span>Status Order</span>
                <select wire:model.live="order_status">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Diproses</option>
                    <option value="shipped">Dikirim</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </label>
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
                        <td colspan="8">Belum ada order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination-v2">
            {{ $this->orders->links() }}
        </div>
    </div>
</div>