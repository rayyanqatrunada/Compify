<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Customer')]
class extends Component {
    public function customers()
    {
        return User::where('role', 'customer')->latest()->get();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Customer</h2>
        <p>Daftar akun customer yang terdaftar.</p>
    </div>

    <div class="admin-panel-v2">
        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Kota</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->customers() as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->city ?? '-' }}</td>
                        <td>{{ $customer->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada customer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>