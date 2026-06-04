<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Dashboard')]
class extends Component {
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD STATUS RULES
    |--------------------------------------------------------------------------
    | Ubah array ini kalau nama status di database kamu berbeda.
    */

    private const PAID_PAYMENT_STATUSES = [
        'paid',
        'settlement',
        'capture',
    ];

    private const PENDING_PAYMENT_STATUSES = [
        'pending',
    ];

    private const BAD_PAYMENT_STATUSES = [
        'failed',
        'expired',
        'deny',
        'cancel',
        'cancelled',
    ];

    private const BAD_ORDER_STATUSES = [
        'failed',
        'expired',
        'cancel',
        'cancelled',
        'canceled',
        'refunded',
    ];

    private function validOrdersQuery()
    {
        return Order::query()
            ->whereNotIn('payment_status', self::BAD_PAYMENT_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES);
    }

    private function paidOrdersQuery()
    {
        return Order::query()
            ->whereIn('payment_status', self::PAID_PAYMENT_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES);
    }

    #[Computed]
    public function totalOrders()
    {
        return $this->validOrdersQuery()->count();
    }

    #[Computed]
    public function totalCustomers()
    {
        return User::where('role', 'customer')->count();
    }

    #[Computed]
    public function totalRevenue()
    {
        return $this->paidOrdersQuery()->sum('total_amount');
    }

    #[Computed]
    public function totalProducts()
    {
        return Product::count();
    }

    #[Computed]
    public function totalAdmins()
    {
        return User::where('role', 'admin')->count();
    }

    #[Computed]
    public function latestOrders()
    {
        return $this->validOrdersQuery()
            ->latest()
            ->limit(7)
            ->get();
    }

    #[Computed]
    public function topSellingProducts()
    {
        if (! DB::getSchemaBuilder()->hasTable('order_items')) {
            return collect();
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_sales')
            )
            ->whereIn('orders.payment_status', self::PAID_PAYMENT_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function revenueChart()
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i));

        $values = $months->map(function ($date) {
            return (float) $this->paidOrdersQuery()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_amount');
        });

        $max = max($values->max(), 1);
        $width = 720;
        $height = 260;

        $points = $values->map(function ($value, $index) use ($max, $width, $height) {
            $x = $index * ($width / 11);
            $y = $height - (($value / $max) * ($height - 30)) - 15;

            return round($x, 2) . ',' . round($y, 2);
        })->implode(' ');

        return [
            'labels' => $months->map(fn ($date) => $date->format('M'))->values(),
            'values' => $values,
            'points' => $points,
            'max' => $max,
            'width' => $width,
            'height' => $height,
        ];
    }

    public function statusPercent(string $status): int
    {
        $total = max($this->totalOrders, 1);

        $count = $this->validOrdersQuery()
            ->where('order_status', $status)
            ->count();

        return (int) round(($count / $total) * 100);
    }
};
?>

<div class="admin-dashboard-v2">
    <section class="admin-stat-row-v2">
        <div class="admin-stat-card-v2">
            <div>
                <strong>{{ $this->totalOrders }}</strong>
                <span>Total Orders</span>
            </div>
            <i>▧</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>{{ $this->totalCustomers }}</strong>
                <span>Total Customers</span>
            </div>
            <i>☻</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}</strong>
                <span>Total Revenue</span>
            </div>
            <i>◎</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>{{ $this->totalProducts }}</strong>
                <span>Total Products</span>
            </div>
            <i>▤</i>
        </div>

        <div class="admin-stat-card-v2">
            <div>
                <strong>{{ $this->totalAdmins }}</strong>
                <span>Total Admin</span>
            </div>
            <i>♙</i>
        </div>
    </section>

    <section class="admin-grid-v2">
        <div class="admin-panel-v2 order-summary-panel-v2">
            <div class="admin-panel-head-v2">
                <div>
                    <h2>Order Summary</h2>
                    <p>Status pesanan saat ini</p>
                </div>
            </div>

            <div class="admin-ring-row-v2">
                <div class="admin-ring-v2" style="--value: {{ $this->statusPercent('pending') }}">
                    <div>
                        <strong>{{ $this->statusPercent('pending') }}%</strong>
                        <span>Pending</span>
                    </div>
                </div>

                <div class="admin-ring-v2" style="--value: {{ $this->statusPercent('processing') }}">
                    <div>
                        <strong>{{ $this->statusPercent('processing') }}%</strong>
                        <span>Processing</span>
                    </div>
                </div>

                <div class="admin-ring-v2" style="--value: {{ $this->statusPercent('completed') }}">
                    <div>
                        <strong>{{ $this->statusPercent('completed') }}%</strong>
                        <span>Completed</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-panel-v2">
            <div class="admin-panel-head-v2">
                <div>
                    <h2>Top Selling Products</h2>
                    <p>Produk paling laris</p>
                </div>
                <a href="{{ route('admin.sales.orders') }}" wire:navigate>View All</a>
            </div>

            <div class="admin-selling-list-v2">
                @forelse($this->topSellingProducts as $item)
                    <div>
                        <span>{{ strtoupper(substr($item->product_name, 0, 2)) }}</span>
                        <div>
                            <strong>{{ $item->product_name }}</strong>
                            <small>{{ $item->total_sold }} sold</small>
                        </div>
                        <b>Rp {{ number_format($item->total_sales, 0, ',', '.') }}</b>
                    </div>
                @empty
                    <div class="admin-empty-v2">
                        Belum ada data penjualan.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="admin-grid-v2 wide">
        <div class="admin-panel-v2 revenue-panel-v2">
            <div class="admin-panel-head-v2">
                <div>
                    <h2>Total Revenue</h2>
                    <p>Grafik pendapatan 12 bulan terakhir</p>
                </div>
            </div>

            <div class="admin-line-chart-v2">
                <svg viewBox="0 0 {{ $this->revenueChart['width'] }} {{ $this->revenueChart['height'] }}" preserveAspectRatio="none">
                    <polyline
                        points="{{ $this->revenueChart['points'] }}"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    @foreach(explode(' ', $this->revenueChart['points']) as $point)
                        @php [$x, $y] = explode(',', $point); @endphp
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="currentColor" />
                    @endforeach
                </svg>

                <div class="admin-chart-labels-v2">
                    @foreach($this->revenueChart['labels'] as $label)
                        <span>{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="admin-panel-v2">
            <div class="admin-panel-head-v2">
                <div>
                    <h2>Recent Orders</h2>
                    <p>Pesanan terbaru</p>
                </div>
                <a href="{{ route('admin.sales.orders') }}" wire:navigate>View All</a>
            </div>

            <div class="admin-order-list-v2">
                @forelse($this->latestOrders as $order)
                    <div>
                        <strong>{{ $order->order_number }}</strong>
                        <span>{{ $order->customer_name ?? 'Guest Customer' }}</span>
                        <b>{{ ucfirst($order->order_status) }}</b>
                    </div>
                @empty
                    <div class="admin-empty-v2">
                        Belum ada order.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>