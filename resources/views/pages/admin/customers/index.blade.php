<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Customer')]
class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        return User::query()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->where('role', 'customer')
            ->when(trim($this->search) !== '', function ($query) {
                $keyword = '%' . trim($this->search) . '%';

                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', $keyword)
                        ->orWhere('username', 'like', $keyword)
                        ->orWhere('email', 'like', $keyword)
                        ->orWhere('phone', 'like', $keyword)
                        ->orWhere('city', 'like', $keyword)
                        ->orWhere('province', 'like', $keyword);
                });
            })
            ->latest()
            ->paginate(15);
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Customer</h2>
        <p>Daftar akun customer yang terdaftar.</p>
    </div>

    <div class="admin-panel-v2">
        <div class="admin-toolbar-v2">
            <label class="admin-search-v2">
                <span>Cari customer</span>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Nama, email, phone, kota...">
            </label>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Kota</th>
                    <th>Total Order</th>
                    <th>Total Belanja</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->customers as $customer)
                    <tr>
                        <td>
                            <strong>{{ $customer->name }}</strong>
                            @if($customer->username)
                                <small>{{ '@' . $customer->username }}</small>
                            @endif
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->city ?? '-' }}</td>
                        <td>{{ $customer->orders_count }}</td>
                        <td>Rp {{ number_format((float) ($customer->total_spent ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $customer->created_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Belum ada customer yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination-v2">
            {{ $this->customers->links() }}
        </div>
    </div>
</div>
