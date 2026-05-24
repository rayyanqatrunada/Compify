<?php

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.admin')]
#[Title('Dashboard Admin - Compify')]
class extends Component {
    #[Computed]
    public function totalRevenue()
    {
        return Order::where('payment_status', 'paid')->sum('total_amount');
    }

    #[Computed]
    public function totalOrders()
    {
        return Order::count();
    }

    #[Computed]
    public function pendingOrders()
    {
        return Order::where('order_status', 'pending')->count();
    }

    #[Computed]
    public function paidOrders()
    {
        return Order::where('payment_status', 'paid')->count();
    }

    #[Computed]
    public function totalAccounts()
    {
        return User::count();
    }

    #[Computed]
    public function adminAccounts()
    {
        return User::where('role', 'admin')->count();
    }

    #[Computed]
    public function customerAccounts()
    {
        return User::where('role', 'customer')->count();
    }

    #[Computed]
    public function totalProducts()
    {
        return Product::count();
    }

    #[Computed]
    public function activeProducts()
    {
        return Product::where('is_active', true)->count();
    }

    #[Computed]
    public function featuredProducts()
    {
        return Product::where('is_featured', true)->count();
    }

    #[Computed]
    public function newProducts()
    {
        return Product::where('is_new', true)->count();
    }

    #[Computed]
    public function lowStockProducts()
    {
        return Product::with(['category', 'brand'])
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function latestProducts()
    {
        return Product::with(['category', 'brand'])
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function latestOrders()
    {
        return Order::latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function catalogSummary()
    {
        return [
            'categories' => Category::count(),
            'brands' => Brand::count(),
            'banners' => Banner::count(),
            'active_banners' => Banner::where('is_active', true)->count(),
        ];
    }

    #[Computed]
    public function monthlyRevenue()
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $totals = $months->map(function ($date) {
            return (float) Order::where('payment_status', 'paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_amount');
        });

        $max = max($totals->max(), 1);

        return $months->map(function ($date, $index) use ($totals, $max) {
            $amount = $totals[$index];

            return [
                'label' => $date->format('M'),
                'amount' => $amount,
                'height' => max(6, round(($amount / $max) * 100)),
            ];
        });
    }
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Overview</p>
            <h2>Dashboard</h2>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.catalog.products') }}" wire:navigate>Tambah Produk</a>
            <a href="{{ route('admin.content.banners') }}" wire:navigate>Atur Banner</a>
        </div>
    </div>

    <div class="admin-hero-dashboard">
        <div>
            <p>Total Revenue</p>
            <h3>Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}</h3>
            <span>{{ $this->paidOrders }} paid orders dari {{ $this->totalOrders }} total orders</span>
        </div>

        <div class="admin-hero-mini">
            <strong>{{ $this->totalAccounts }}</strong>
            <span>Total Akun</span>
        </div>

        <div class="admin-hero-mini">
            <strong>{{ $this->totalProducts }}</strong>
            <span>Total Produk</span>
        </div>
    </div>

    <div class="admin-stat-grid admin-stat-grid-4">
        <div class="admin-stat-card">
            <span>Orders</span>
            <strong>{{ $this->totalOrders }}</strong>
            <p>{{ $this->pendingOrders }} pending order</p>
        </div>

        <div class="admin-stat-card">
            <span>Accounts</span>
            <strong>{{ $this->totalAccounts }}</strong>
            <p>{{ $this->adminAccounts }} admin / {{ $this->customerAccounts }} customer</p>
        </div>

        <div class="admin-stat-card">
            <span>Active Products</span>
            <strong>{{ $this->activeProducts }}</strong>
            <p>{{ $this->featuredProducts }} unggulan, {{ $this->newProducts }} baru</p>
        </div>

        <div class="admin-stat-card">
            <span>Catalog</span>
            <strong>{{ $this->catalogSummary['categories'] }}</strong>
            <p>{{ $this->catalogSummary['brands'] }} brands / {{ $this->catalogSummary['active_banners'] }} active banners</p>
        </div>
    </div>

    <div class="admin-dashboard-grid">
        <div class="admin-panel admin-chart-panel">
            <div class="admin-panel-head">
                <div>
                    <p>Revenue</p>
                    <h3>Grafik Penghasilan</h3>
                </div>

                <span class="admin-panel-badge">6 Bulan</span>
            </div>

            <div class="admin-revenue-chart">
                @foreach($this->monthlyRevenue as $month)
                    <div class="admin-chart-item">
                        <div class="admin-chart-bar-wrap">
                            <div class="admin-chart-bar" style="height: {{ $month['height'] }}%;"></div>
                        </div>

                        <span>{{ $month['label'] }}</span>
                        <small>Rp {{ number_format($month['amount'], 0, ',', '.') }}</small>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <p>Sales</p>
                    <h3>Pesanan Terbaru</h3>
                </div>

                <a href="{{ route('admin.sales.orders') }}" wire:navigate>View All</a>
            </div>

            <div class="admin-list">
                @forelse($this->latestOrders as $order)
                    <div class="admin-list-item">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <span>{{ $order->customer_name ?? 'Guest Customer' }}</span>
                        </div>

                        <b>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</b>
                    </div>
                @empty
                    <div class="admin-empty">
                        Belum ada pesanan.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="admin-dashboard-grid">
        <div class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <p>Inventory</p>
                    <h3>Low Stock Products</h3>
                </div>

                <a href="{{ route('admin.catalog.products') }}" wire:navigate>Manage</a>
            </div>

            <div class="admin-list">
                @forelse($this->lowStockProducts as $product)
                    <div class="admin-list-item">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->category?->name ?? 'No category' }}</span>
                        </div>

                        <b>{{ $product->stock }} left</b>
                    </div>
                @empty
                    <div class="admin-empty">
                        Semua stok masih aman.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <p>Catalog</p>
                    <h3>Produk Terbaru</h3>
                </div>

                <a href="{{ route('admin.catalog.products') }}" wire:navigate>Manage</a>
            </div>

            <div class="admin-list">
                @forelse($this->latestProducts as $product)
                    <div class="admin-list-item">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->brand?->name ?? 'No brand' }}</span>
                        </div>

                        <b>Rp {{ number_format($product->sale_price ?: $product->price, 0, ',', '.') }}</b>
                    </div>
                @empty
                    <div class="admin-empty">
                        Belum ada produk.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>