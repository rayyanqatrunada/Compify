<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Analytic')]
class extends Component {
    public function revenue()
    {
        return Order::where('payment_status', 'paid')->sum('total_amount');
    }

    public function estimatedProfit()
    {
        return $this->revenue() * 0.2;
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Analytic</h2>
        <p>Ringkasan data penjualan, customer, produk, dan estimasi profit.</p>
    </div>

    <div class="admin-stat-row-v2">
        <div class="admin-stat-card-v2">
            <div>
                <strong>Rp {{ number_format($this->revenue(), 0, ',', '.') }}</strong>
                <span>Total Penjualan</span>
            </div>
            <i>◎</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>Rp {{ number_format($this->estimatedProfit(), 0, ',', '.') }}</strong>
                <span>Estimasi Profit</span>
            </div>
            <i>↗</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>{{ User::where('role', 'customer')->count() }}</strong>
                <span>Customer</span>
            </div>
            <i>☻</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>{{ Product::count() }}</strong>
                <span>Produk</span>
            </div>
            <i>▤</i>
        </div>
    </div>

    <div class="admin-panel-v2">
        <h2>Catatan</h2>
        <p>
            Data untung rugi saat ini masih estimasi karena produk belum memiliki field HPP/modal.
            Kalau ingin profit benar-benar akurat, nanti tambahkan kolom <b>cost_price</b> pada produk.
        </p>
    </div>
</div>