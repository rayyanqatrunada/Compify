<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Analytics')]
class extends Component {

    /*
    |--------------------------------------------------------------------------
    | KONSTANTA STATUS
    |--------------------------------------------------------------------------
    */
    private const PAID_STATUSES         = ['paid', 'settlement', 'capture'];
    private const PENDING_STATUSES      = ['pending'];
    private const BAD_PAYMENT_STATUSES  = ['failed', 'expired', 'deny', 'cancel', 'cancelled'];
    private const BAD_ORDER_STATUSES    = ['failed', 'expired', 'cancel', 'cancelled', 'canceled', 'refunded'];

    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */
    #[Url(as: 'tab')]
    public string $activeTab = 'sales';

    #[Url(as: 'range')]
    public string $dateRange = '30';

    public ?string $customFrom = null;
    public ?string $customTo   = null;

    /*
    |--------------------------------------------------------------------------
    | HELPER RENTANG TANGGAL
    | Menggunakan CarbonImmutable agar kompatibel dengan Laravel 13
    | yang mengembalikan CarbonImmutable dari now() secara default.
    | CarbonImmutable tidak perlu ->copy() karena setiap operasi
    | selalu menghasilkan instance baru (tidak pernah mutasi).
    |--------------------------------------------------------------------------
    */
    private function startDate(): \Carbon\CarbonImmutable
    {
        return match ($this->dateRange) {
            'today'  => \Carbon\CarbonImmutable::now()->startOfDay(),
            '7'      => \Carbon\CarbonImmutable::now()->subDays(6)->startOfDay(),
            '30'     => \Carbon\CarbonImmutable::now()->subDays(29)->startOfDay(),
            '90'     => \Carbon\CarbonImmutable::now()->subDays(89)->startOfDay(),
            'custom' => $this->customFrom
                ? \Carbon\CarbonImmutable::parse($this->customFrom)->startOfDay()
                : \Carbon\CarbonImmutable::now()->subDays(29)->startOfDay(),
            default  => \Carbon\CarbonImmutable::now()->subDays(29)->startOfDay(),
        };
    }

    private function endDate(): \Carbon\CarbonImmutable
    {
        return match ($this->dateRange) {
            'custom' => $this->customTo
                ? \Carbon\CarbonImmutable::parse($this->customTo)->endOfDay()
                : \Carbon\CarbonImmutable::now()->endOfDay(),
            default => \Carbon\CarbonImmutable::now()->endOfDay(),
        };
    }

    private function previousStartDate(): \Carbon\CarbonImmutable
    {
        $start = $this->startDate();
        $end   = $this->endDate();
        $diff  = (int) $start->diffInDays($end) + 1;

        // CarbonImmutable tidak perlu ->copy(), subDays() langsung menghasilkan instance baru
        return $start->subDays($diff);
    }

    private function previousEndDate(): \Carbon\CarbonImmutable
    {
        return $this->startDate()->subSecond()->endOfDay();
    }

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */
    private function paidQuery()
    {
        return Order::query()
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()]);
    }

    private function validQuery()
    {
        return Order::query()
            ->whereNotIn('payment_status', self::BAD_PAYMENT_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()]);
    }

    private function prevPaidQuery()
    {
        return Order::query()
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$this->previousStartDate(), $this->previousEndDate()]);
    }

    /*
    |--------------------------------------------------------------------------
    | AKSI TAB
    |--------------------------------------------------------------------------
    */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function applyCustomRange(): void
    {
        $this->dateRange = 'custom';
    }

    /*
    |--------------------------------------------------------------------------
    | ANALITIK PENJUALAN
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function salesRevenue(): float
    {
        return (float) $this->paidQuery()->sum('total_amount');
    }

    #[Computed]
    public function salesRevenuePrev(): float
    {
        return (float) $this->prevPaidQuery()->sum('total_amount');
    }

    #[Computed]
    public function salesRevenueTrend(): float
    {
        $prev = $this->salesRevenuePrev;
        if ($prev <= 0) return 0;
        return round((($this->salesRevenue - $prev) / $prev) * 100, 1);
    }

    #[Computed]
    public function totalOrders(): int
    {
        return $this->validQuery()->count();
    }

    #[Computed]
    public function paidOrders(): int
    {
        return $this->paidQuery()->count();
    }

    #[Computed]
    public function pendingOrders(): int
    {
        return Order::query()
            ->whereIn('payment_status', self::PENDING_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()])
            ->count();
    }

    #[Computed]
    public function averageOrderValue(): float
    {
        $count = $this->paidOrders;
        return $count > 0 ? round($this->salesRevenue / $count, 2) : 0;
    }

    #[Computed]
    public function revenueChartData(): array
    {
        $start = $this->startDate();
        $end   = $this->endDate();
        // CarbonImmutable: diffInDays() langsung bisa dipanggil tanpa ->copy()
        $days  = max((int) $start->diffInDays($end) + 1, 1);

        if ($days <= 31) {
            $dbFormat = '%Y-%m-%d';
            $points   = collect();
            for ($i = 0; $i < $days; $i++) {
                $points->push($start->addDays($i)->format('Y-m-d'));
            }
        } else {
            $dbFormat = '%Y-%m-%d';
            $points   = collect();
            $cur      = $start->startOfWeek(\Carbon\Carbon::MONDAY);
            while ($cur->lte($end)) {
                $points->push($cur->format('Y-m-d'));
                $cur = $cur->addWeek();
            }
        }

        $rows = DB::table('orders')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dbFormat}') as period"),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->pluck('revenue', 'period');

        $labels  = [];
        $revenue = [];

        if ($days <= 31) {
            foreach ($points as $pt) {
                $labels[]  = \Carbon\CarbonImmutable::parse($pt)->format('d M');
                $revenue[] = (float) ($rows[$pt] ?? 0);
            }
        } else {
            foreach ($points as $weekStart) {
                $weekRevenue = 0;
                for ($d = 0; $d < 7; $d++) {
                    $day         = \Carbon\CarbonImmutable::parse($weekStart)->addDays($d)->format('Y-m-d');
                    $weekRevenue += (float) ($rows[$day] ?? 0);
                }
                $labels[]  = 'W' . \Carbon\CarbonImmutable::parse($weekStart)->format('W');
                $revenue[] = $weekRevenue;
            }
        }

        return ['labels' => $labels, 'values' => $revenue];
    }

    #[Computed]
    public function orderStatusBreakdown(): array
    {
        return Order::query()
            ->whereNotIn('payment_status', self::BAD_PAYMENT_STATUSES)
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()])
            ->select('order_status', DB::raw('count(*) as total'))
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->toArray();
    }

    #[Computed]
    public function paymentMethodBreakdown(): array
    {
        return DB::table('orders')
            ->join('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
            ->select('payment_methods.name', DB::raw('count(*) as total'), DB::raw('sum(orders.total_amount) as revenue'))
            ->whereIn('orders.payment_status', self::PAID_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('orders.created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | ANALITIK PRODUK
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function topSellingProducts(): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                'order_items.product_name',
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_revenue'),
                DB::raw('SUM(order_items.discount_amount * order_items.quantity) as total_discount'),
                DB::raw('AVG(order_items.price) as avg_price')
            )
            ->whereIn('orders.payment_status', self::PAID_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('orders.created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('order_items.product_name', 'order_items.product_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function worstSellingProducts(): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                'order_items.product_name',
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->whereIn('orders.payment_status', self::PAID_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('orders.created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('order_items.product_name', 'order_items.product_id')
            ->orderBy('total_sold')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function revenuePerProduct(): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.total) as total_revenue'),
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.discount_amount * order_items.quantity) as total_disc')
            )
            ->whereIn('orders.payment_status', self::PAID_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('orders.created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | ANALITIK PELANGGAN
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function totalCustomers(): int
    {
        return User::where('role', 'customer')->count();
    }

    #[Computed]
    public function newCustomers(): int
    {
        return User::where('role', 'customer')
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()])
            ->count();
    }

    #[Computed]
    public function returningCustomers(): int
    {
        return DB::table('orders')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    #[Computed]
    public function topCustomers(): \Illuminate\Support\Collection
    {
        return DB::table('orders')
            ->select(
                'user_id',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('MAX(customer_email) as customer_email'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MAX(created_at) as last_order')
            )
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function customerGrowthChart(): array
    {
        $start = $this->startDate();
        $end   = $this->endDate();
        $days  = max((int) $start->diffInDays($end) + 1, 1);

        $rows = DB::table('users')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as day"), DB::raw('count(*) as total'))
            ->where('role', 'customer')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        $points = min($days, 30);
        $step   = max(1, (int) ceil($days / $points));

        for ($i = 0; $i < $days; $i += $step) {
            $d        = $start->addDays($i)->format('Y-m-d');
            $labels[] = \Carbon\CarbonImmutable::parse($d)->format('d M');
            $values[] = (int) ($rows[$d] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /*
    |--------------------------------------------------------------------------
    | ANALITIK INVENTARIS
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function outOfStockProducts(): \Illuminate\Support\Collection
    {
        return Product::where('stock', 0)
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'stock', 'price', 'sale_price', 'image')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function lowStockProducts(): \Illuminate\Support\Collection
    {
        return Product::where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'stock', 'price', 'sale_price', 'image')
            ->orderBy('stock')
            ->get();
    }

    #[Computed]
    public function fastMovingProducts(): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.stock',
                'products.price',
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->whereIn('orders.payment_status', self::PAID_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('orders.created_at', [$this->startDate(), $this->endDate()])
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.stock', 'products.price')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function restockRecommendations(): \Illuminate\Support\Collection
    {
        $startDate        = $this->startDate();
        $endDate          = $this->endDate();
        $paidStatuses     = self::PAID_STATUSES;
        $badOrderStatuses = self::BAD_ORDER_STATUSES;

        return DB::table('products')
            ->leftJoin('order_items', function ($join) use ($startDate, $endDate, $paidStatuses, $badOrderStatuses) {
                $join->on('products.id', '=', 'order_items.product_id')
                    ->whereExists(function ($q) use ($startDate, $endDate, $paidStatuses, $badOrderStatuses) {
                        $q->from('orders')
                            ->whereColumn('orders.id', 'order_items.order_id')
                            ->whereIn('orders.payment_status', $paidStatuses)
                            ->whereNotIn('orders.order_status', $badOrderStatuses)
                            ->whereBetween('orders.created_at', [$startDate, $endDate]);
                    });
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.stock',
                'products.price',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as recent_sold')
            )
            ->where('products.stock', '<=', 5)
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.stock', 'products.price')
            ->orderBy('products.stock')
            ->limit(10)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | ANALITIK PROFIT / PENDAPATAN
    |--------------------------------------------------------------------------
    */
    #[Computed]
    public function grossRevenue(): float
    {
        return (float) $this->paidQuery()->sum('total_amount');
    }

    #[Computed]
    public function grossRevenuePrev(): float
    {
        return (float) $this->prevPaidQuery()->sum('total_amount');
    }

    #[Computed]
    public function grossRevenueTrend(): float
    {
        $prev = $this->grossRevenuePrev;
        if ($prev <= 0) return 0;
        return round((($this->grossRevenue - $prev) / $prev) * 100, 1);
    }

    #[Computed]
    public function discountTotals(): object
    {
        return $this->paidQuery()
            ->selectRaw('SUM(discount_amount) as item_discount, SUM(universal_discount_amount) as universal_discount')
            ->first();
    }

    #[Computed]
    public function totalDiscount(): float
    {
        return (float) ($this->discountTotals->item_discount ?? 0)
             + (float) ($this->discountTotals->universal_discount ?? 0);
    }

    #[Computed]
    public function netRevenue(): float
    {
        return max(0, $this->grossRevenue - $this->totalDiscount);
    }

    #[Computed]
    public function totalShippingRevenue(): float
    {
        return (float) $this->paidQuery()->sum('shipping_cost');
    }

    #[Computed]
    public function discountImpact(): float
    {
        $gross = $this->grossRevenue;
        if ($gross <= 0) return 0;
        return round(($this->totalDiscount / $gross) * 100, 1);
    }

    #[Computed]
    public function revenueByPeriodChart(): array
    {
        return $this->revenueChartData;
    }

    #[Computed]
    public function topProfitableProducts(): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.total) as gross_revenue'),
                DB::raw('SUM(order_items.discount_amount * order_items.quantity) as total_discount'),
                DB::raw('SUM(order_items.total) - SUM(order_items.discount_amount * order_items.quantity) as net_revenue'),
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->whereIn('orders.payment_status', self::PAID_STATUSES)
            ->whereNotIn('orders.order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('orders.created_at', [$this->startDate(), $this->endDate()])
            ->groupBy('order_items.product_name')
            ->orderByDesc('net_revenue')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function discountTrendChart(): array
    {
        $start = $this->startDate();
        $end   = $this->endDate();
        $days  = max((int) $start->diffInDays($end) + 1, 1);

        $rows = DB::table('orders')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as day"),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('SUM(discount_amount + universal_discount_amount) as discount')
            )
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereNotIn('order_status', self::BAD_ORDER_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels   = [];
        $revenue  = [];
        $discount = [];
        $step     = max(1, (int) ceil($days / 30));

        for ($i = 0; $i < $days; $i += $step) {
            $d          = $start->addDays($i)->format('Y-m-d');
            $labels[]   = \Carbon\CarbonImmutable::parse($d)->format('d M');
            $revenue[]  = (float) ($rows[$d]->revenue  ?? 0);
            $discount[] = (float) ($rows[$d]->discount ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'discount' => $discount];
    }

};
?>

{{-- ============================================================
     TAMPILAN HALAMAN ANALYTICS
     ============================================================ --}}
<div class="an-page">

    {{-- ── HEADER ── --}}
    <div class="an-header">
        <div class="an-header-left">
            <h1>Analitik</h1>
            <p>Wawasan bisnis & ringkasan performa toko</p>
        </div>

        {{-- PEMILIH RENTANG TANGGAL --}}
        <div class="an-range-bar">
            @foreach([
                'today' => 'Hari Ini',
                '7'     => '7 Hari',
                '30'    => '30 Hari',
                '90'    => '3 Bulan',
                'custom'=> 'Kustom',
            ] as $key => $label)
                <button
                    class="an-range-btn {{ $dateRange === $key ? 'active' : '' }}"
                    wire:click="$set('dateRange', '{{ $key }}')"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- Input tanggal kustom --}}
    @if($dateRange === 'custom')
    <div class="an-custom-range">
        <div class="an-custom-field">
            <label>Dari</label>
            <input type="date" wire:model="customFrom" class="an-date-input" />
        </div>
        <span class="an-custom-sep">→</span>
        <div class="an-custom-field">
            <label>Sampai</label>
            <input type="date" wire:model="customTo" class="an-date-input" />
        </div>
        <button class="an-apply-btn" wire:click="applyCustomRange">Terapkan</button>
    </div>
    @endif

    {{-- ── NAVIGASI TAB ── --}}
    <nav class="admin-tabs-v2">
        @foreach([
            'sales'     => ['icon' => '◈', 'label' => 'Penjualan'],
            'product'   => ['icon' => '▤', 'label' => 'Produk'],
            'customer'  => ['icon' => '◉', 'label' => 'Pelanggan'],
            'inventory' => ['icon' => '◫', 'label' => 'Inventaris'],
            'profit'    => ['icon' => '◎', 'label' => 'Pendapatan & Profit'],
        ] as $tab => $meta)
            <button
                class="an-tab {{ $activeTab === $tab ? 'active' : '' }}"
                wire:click="setTab('{{ $tab }}')"
            >
                <span class="an-tab-icon">{{ $meta['icon'] }}</span>
                {{ $meta['label'] }}
            </button>
        @endforeach
    </nav>

    {{-- ═══════════════════════════════════════════════
         TAB: PENJUALAN
    ════════════════════════════════════════════════ --}}
    @if($activeTab === 'sales')
    <div class="an-section" wire:key="tab-sales">

        <div class="an-kpi-grid">
            <div class="an-kpi-card accent-teal">
                <div class="an-kpi-label">Pendapatan Periode Ini</div>
                <div class="an-kpi-value">Rp {{ number_format($this->salesRevenue, 0, ',', '.') }}</div>
                @php $t = $this->salesRevenueTrend; @endphp
                <div class="an-kpi-trend {{ $t >= 0 ? 'up' : 'down' }}">
                    {{ $t >= 0 ? '▲' : '▼' }} {{ abs($t) }}% vs periode sebelumnya
                </div>
            </div>

            <div class="an-kpi-card accent-blue">
                <div class="an-kpi-label">Total Pesanan</div>
                <div class="an-kpi-value">{{ number_format($this->totalOrders) }}</div>
                <div class="an-kpi-sub">Semua status valid</div>
            </div>

            <div class="an-kpi-card accent-green">
                <div class="an-kpi-label">Pesanan Dibayar</div>
                <div class="an-kpi-value">{{ number_format($this->paidOrders) }}</div>
                <div class="an-kpi-sub">
                    {{ $this->totalOrders > 0 ? round(($this->paidOrders / max($this->totalOrders,1)) * 100) : 0 }}% dari total
                </div>
            </div>

            <div class="an-kpi-card accent-amber">
                <div class="an-kpi-label">Pesanan Menunggu</div>
                <div class="an-kpi-value">{{ number_format($this->pendingOrders) }}</div>
                <div class="an-kpi-sub">Menunggu pembayaran</div>
            </div>

            <div class="an-kpi-card accent-purple">
                <div class="an-kpi-label">Rata-rata Nilai Pesanan</div>
                <div class="an-kpi-value">Rp {{ number_format($this->averageOrderValue, 0, ',', '.') }}</div>
                <div class="an-kpi-sub">Per pesanan berbayar</div>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div>
                    <h2>Tren Pendapatan</h2>
                    <p>Pendapatan per periode dalam rentang waktu terpilih</p>
                </div>
            </div>
            <div class="an-chart-wrap">
                @php $chart = $this->revenueChartData; @endphp
                <canvas id="salesRevenueChart" class="an-canvas"
                    data-labels="{{ json_encode($chart['labels']) }}"
                    data-values="{{ json_encode($chart['values']) }}"
                    data-type="line"
                    data-color="teal"
                ></canvas>
            </div>
        </div>

        <div class="an-grid-2">
            <div class="an-panel">
                <div class="an-panel-head">
                    <div><h2>Status Pesanan</h2><p>Rincian status pesanan</p></div>
                </div>
                <div class="an-status-list">
                    @php
                        $statusColors = [
                            'pending'    => 'amber',
                            'processing' => 'blue',
                            'shipped'    => 'purple',
                            'completed'  => 'green',
                            'cancelled'  => 'red',
                        ];
                        $statusLabels = [
                            'pending'    => 'Menunggu',
                            'processing' => 'Diproses',
                            'shipped'    => 'Dikirim',
                            'completed'  => 'Selesai',
                            'cancelled'  => 'Dibatalkan',
                        ];
                        $statusBreak = $this->orderStatusBreakdown;
                        $totalSB = max(array_sum($statusBreak), 1);
                    @endphp
                    @forelse($statusBreak as $status => $count)
                    <div class="an-status-row">
                        <div class="an-status-dot {{ $statusColors[$status] ?? 'gray' }}"></div>
                        <span class="an-status-name">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                        <div class="an-bar-track">
                            <div class="an-bar-fill {{ $statusColors[$status] ?? 'gray' }}" style="width: {{ round(($count/$totalSB)*100) }}%"></div>
                        </div>
                        <span class="an-status-count">{{ $count }}</span>
                        <span class="an-status-pct">{{ round(($count/$totalSB)*100) }}%</span>
                    </div>
                    @empty
                    <div class="an-empty">Belum ada data pesanan.</div>
                    @endforelse
                </div>
            </div>

            <div class="an-panel">
                <div class="an-panel-head">
                    <div><h2>Metode Pembayaran</h2><p>Metode pembayaran yang digunakan pelanggan</p></div>
                </div>
                <div class="an-status-list">
                    @php
                        $pmBreak = $this->paymentMethodBreakdown;
                        $totalPM = max(array_sum(array_column($pmBreak, 'total')), 1);
                    @endphp
                    @forelse($pmBreak as $i => $pm)
                    <div class="an-status-row">
                        <div class="an-status-dot" style="background: var(--an-palette-{{ $i % 5 }})"></div>
                        <span class="an-status-name">{{ $pm->name }}</span>
                        <div class="an-bar-track">
                            <div class="an-bar-fill" style="width: {{ round(($pm->total/$totalPM)*100) }}%; background: var(--an-palette-{{ $i % 5 }})"></div>
                        </div>
                        <span class="an-status-count">{{ $pm->total }}</span>
                        <span class="an-status-pct">Rp {{ number_format($pm->revenue, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="an-empty">Belum ada data metode pembayaran.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         TAB: PRODUK
    ════════════════════════════════════════════════ --}}
    @if($activeTab === 'product')
    <div class="an-section" wire:key="tab-product">

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Produk Terlaris</h2><p>Produk dengan penjualan terbanyak</p></div>
            </div>
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Terjual</th>
                            <th>Pendapatan</th>
                            <th>Total Diskon</th>
                            <th>Harga Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->topSellingProducts as $i => $item)
                        <tr>
                            <td>
                                @if($i < 3)
                                    <span class="an-rank an-rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                                @else
                                    <span class="an-rank-plain">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="an-product-cell">
                                    <span class="an-product-avatar">{{ strtoupper(substr($item->product_name, 0, 2)) }}</span>
                                    <span>{{ $item->product_name }}</span>
                                </div>
                            </td>
                            <td><strong>{{ number_format($item->total_sold) }}</strong></td>
                            <td>Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                            <td class="an-text-red">Rp {{ number_format($item->total_discount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->avg_price, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="an-empty">Belum ada data penjualan produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Pendapatan per Produk</h2><p>10 produk dengan kontribusi pendapatan tertinggi</p></div>
            </div>
            <div class="an-chart-wrap">
                @php $rpp = $this->revenuePerProduct; @endphp
                <canvas id="revenuePerProductChart" class="an-canvas"
                    data-labels="{{ json_encode($rpp->pluck('product_name')->map(fn($n) => strlen($n) > 20 ? substr($n,0,18).'…' : $n)->values()) }}"
                    data-values="{{ json_encode($rpp->pluck('total_revenue')->values()) }}"
                    data-type="bar"
                    data-color="multi"
                ></canvas>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Produk Paling Sedikit Terjual</h2><p>Produk dengan penjualan terendah — perlu perhatian</p></div>
                <span class="an-badge an-badge-red">Perlu Ditinjau</span>
            </div>
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Terjual</th>
                            <th>Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->worstSellingProducts as $i => $item)
                        <tr class="{{ $item->total_sold == 0 ? 'an-row-dim' : '' }}">
                            <td><span class="an-rank-plain">{{ $i + 1 }}</span></td>
                            <td>
                                <div class="an-product-cell">
                                    <span class="an-product-avatar dim">{{ strtoupper(substr($item->product_name, 0, 2)) }}</span>
                                    <span>{{ $item->product_name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="{{ $item->total_sold == 0 ? 'an-badge an-badge-red' : '' }}">
                                    {{ number_format($item->total_sold) }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="an-empty">Semua produk terjual dengan baik.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         TAB: PELANGGAN
    ════════════════════════════════════════════════ --}}
    @if($activeTab === 'customer')
    <div class="an-section" wire:key="tab-customer">

        <div class="an-kpi-grid">
            <div class="an-kpi-card accent-teal">
                <div class="an-kpi-label">Total Pelanggan</div>
                <div class="an-kpi-value">{{ number_format($this->totalCustomers) }}</div>
                <div class="an-kpi-sub">Sepanjang waktu</div>
            </div>
            <div class="an-kpi-card accent-green">
                <div class="an-kpi-label">Pelanggan Baru</div>
                <div class="an-kpi-value">{{ number_format($this->newCustomers) }}</div>
                <div class="an-kpi-sub">Daftar di periode ini</div>
            </div>
            <div class="an-kpi-card accent-blue">
                <div class="an-kpi-label">Pelanggan Kembali</div>
                <div class="an-kpi-value">{{ number_format($this->returningCustomers) }}</div>
                <div class="an-kpi-sub">Beli lebih dari 1x di periode ini</div>
            </div>
            <div class="an-kpi-card accent-purple">
                <div class="an-kpi-label">Tingkat Retensi</div>
                @php $ret = $this->totalCustomers > 0 ? round(($this->returningCustomers / max($this->totalCustomers,1)) * 100, 1) : 0; @endphp
                <div class="an-kpi-value">{{ $ret }}%</div>
                <div class="an-kpi-sub">Pelanggan kembali / total</div>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Pertumbuhan Pelanggan</h2><p>Jumlah pelanggan baru per hari</p></div>
            </div>
            <div class="an-chart-wrap">
                @php $cg = $this->customerGrowthChart; @endphp
                <canvas id="customerGrowthChart" class="an-canvas"
                    data-labels="{{ json_encode($cg['labels']) }}"
                    data-values="{{ json_encode($cg['values']) }}"
                    data-type="bar"
                    data-color="blue"
                ></canvas>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Pelanggan Teratas</h2><p>Pelanggan dengan total belanja terbesar</p></div>
            </div>
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pelanggan</th>
                            <th>Total Pesanan</th>
                            <th>Total Belanja</th>
                            <th>Pesanan Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->topCustomers as $i => $cust)
                        <tr>
                            <td>
                                @if($i < 3)
                                    <span class="an-rank an-rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                                @else
                                    <span class="an-rank-plain">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="an-product-cell">
                                    <span class="an-product-avatar">{{ strtoupper(substr($cust->customer_name ?? 'G', 0, 2)) }}</span>
                                    <div>
                                        <strong>{{ $cust->customer_name ?? 'Tamu' }}</strong>
                                        <small>{{ $cust->customer_email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $cust->total_orders }}x</td>
                            <td><strong>Rp {{ number_format($cust->total_spent, 0, ',', '.') }}</strong></td>
                            <td><span class="an-date">{{ \Carbon\CarbonImmutable::parse($cust->last_order)->diffForHumans() }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="an-empty">Belum ada data pelanggan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         TAB: INVENTARIS
    ════════════════════════════════════════════════ --}}
    @if($activeTab === 'inventory')
    <div class="an-section" wire:key="tab-inventory">

        <div class="an-kpi-grid">
            <div class="an-kpi-card accent-red">
                <div class="an-kpi-label">Stok Habis</div>
                <div class="an-kpi-value">{{ $this->outOfStockProducts->count() }}</div>
                <div class="an-kpi-sub">Produk kehabisan stok</div>
            </div>
            <div class="an-kpi-card accent-amber">
                <div class="an-kpi-label">Stok Menipis</div>
                <div class="an-kpi-value">{{ $this->lowStockProducts->count() }}</div>
                <div class="an-kpi-sub">Stok ≤ 10 unit</div>
            </div>
            <div class="an-kpi-card accent-teal">
                <div class="an-kpi-label">Produk Cepat Laku</div>
                <div class="an-kpi-value">{{ $this->fastMovingProducts->count() }}</div>
                <div class="an-kpi-sub">Produk laris periode ini</div>
            </div>
            <div class="an-kpi-card accent-purple">
                <div class="an-kpi-label">Perlu Restock</div>
                <div class="an-kpi-value">{{ $this->restockRecommendations->count() }}</div>
                <div class="an-kpi-sub">Stok ≤ 5 & masih diminati</div>
            </div>
        </div>

        @if($this->outOfStockProducts->count())
        <div class="an-panel an-panel-alert">
            <div class="an-panel-head">
                <div><h2>⚠ Stok Habis</h2><p>Produk aktif yang kehabisan stok — segera lakukan restock!</p></div>
                <span class="an-badge an-badge-red">{{ $this->outOfStockProducts->count() }} produk</span>
            </div>
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead><tr><th>Produk</th><th>SKU</th><th>Harga</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($this->outOfStockProducts as $p)
                        <tr>
                            <td>
                                <div class="an-product-cell">
                                    <span class="an-product-avatar red">{{ strtoupper(substr($p->name, 0, 2)) }}</span>
                                    {{ $p->name }}
                                </div>
                            </td>
                            <td><code>{{ $p->sku ?? '-' }}</code></td>
                            <td>Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                            <td><span class="an-badge an-badge-red">Habis</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Produk Stok Menipis</h2><p>Sisa stok 1–10 unit</p></div>
            </div>
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead><tr><th>Produk</th><th>SKU</th><th>Stok</th><th>Harga</th><th>Level</th></tr></thead>
                    <tbody>
                        @forelse($this->lowStockProducts as $p)
                        <tr>
                            <td>
                                <div class="an-product-cell">
                                    <span class="an-product-avatar amber">{{ strtoupper(substr($p->name, 0, 2)) }}</span>
                                    {{ $p->name }}
                                </div>
                            </td>
                            <td><code>{{ $p->sku ?? '-' }}</code></td>
                            <td><strong>{{ $p->stock }}</strong></td>
                            <td>Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                            <td>
                                <div class="an-stock-bar">
                                    <div class="an-stock-fill {{ $p->stock <= 3 ? 'critical' : 'low' }}" style="width: {{ min(100, $p->stock * 10) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="an-empty">Semua produk stoknya masih aman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="an-grid-2">
            <div class="an-panel">
                <div class="an-panel-head">
                    <div><h2>Rekomendasi Restock</h2><p>Produk stok kritis yang masih diminati pembeli</p></div>
                </div>
                <div class="an-restock-list">
                    @forelse($this->restockRecommendations as $p)
                    <div class="an-restock-row">
                        <div class="an-restock-info">
                            <span class="an-product-avatar {{ $p->stock == 0 ? 'red' : 'amber' }}">{{ strtoupper(substr($p->name, 0, 2)) }}</span>
                            <div>
                                <strong>{{ $p->name }}</strong>
                                <small>Stok: {{ $p->stock }} | Terjual: {{ $p->recent_sold }}x</small>
                            </div>
                        </div>
                        <span class="an-badge {{ $p->stock == 0 ? 'an-badge-red' : 'an-badge-amber' }}">
                            {{ $p->stock == 0 ? 'Habis' : 'Kritis' }}
                        </span>
                    </div>
                    @empty
                    <div class="an-empty">Tidak ada rekomendasi restock saat ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="an-panel">
                <div class="an-panel-head">
                    <div><h2>Produk Cepat Laku</h2><p>Produk yang paling banyak terjual dalam periode ini</p></div>
                </div>
                <div class="an-restock-list">
                    @forelse($this->fastMovingProducts as $i => $p)
                    <div class="an-restock-row">
                        <div class="an-restock-info">
                            <span class="an-product-avatar">{{ strtoupper(substr($p->name, 0, 2)) }}</span>
                            <div>
                                <strong>{{ $p->name }}</strong>
                                <small>Sisa stok: {{ $p->stock }} unit</small>
                            </div>
                        </div>
                        <div class="an-fast-sold">
                            <strong>{{ number_format($p->total_sold) }}</strong>
                            <small>terjual</small>
                        </div>
                    </div>
                    @empty
                    <div class="an-empty">Belum ada data penjualan produk.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         TAB: PENDAPATAN & PROFIT
    ════════════════════════════════════════════════ --}}
    @if($activeTab === 'profit')
    <div class="an-section" wire:key="tab-profit">

        <div class="an-info-note">
            <span>ℹ</span>
            <p>Profit dihitung sebagai <strong>Pendapatan Kotor − Total Diskon</strong> karena data HPP (harga modal) belum tersedia. Angka ini merepresentasikan <em>Pendapatan Bersih</em> setelah diskon.</p>
        </div>

        <div class="an-kpi-grid">
            <div class="an-kpi-card accent-teal">
                <div class="an-kpi-label">Pendapatan Kotor</div>
                <div class="an-kpi-value">Rp {{ number_format($this->grossRevenue, 0, ',', '.') }}</div>
                @php $gt = $this->grossRevenueTrend; @endphp
                <div class="an-kpi-trend {{ $gt >= 0 ? 'up' : 'down' }}">
                    {{ $gt >= 0 ? '▲' : '▼' }} {{ abs($gt) }}% vs periode sebelumnya
                </div>
            </div>

            <div class="an-kpi-card accent-red">
                <div class="an-kpi-label">Total Diskon</div>
                <div class="an-kpi-value">Rp {{ number_format($this->totalDiscount, 0, ',', '.') }}</div>
                <div class="an-kpi-sub">{{ $this->discountImpact }}% dari pendapatan kotor</div>
            </div>

            <div class="an-kpi-card accent-green">
                <div class="an-kpi-label">Pendapatan Bersih</div>
                <div class="an-kpi-value">Rp {{ number_format($this->netRevenue, 0, ',', '.') }}</div>
                <div class="an-kpi-sub">Kotor − Diskon</div>
            </div>

            <div class="an-kpi-card accent-blue">
                <div class="an-kpi-label">Pendapatan Ongkir</div>
                <div class="an-kpi-value">Rp {{ number_format($this->totalShippingRevenue, 0, ',', '.') }}</div>
                <div class="an-kpi-sub">Ongkos kirim dari pesanan berbayar</div>
            </div>

            <div class="an-kpi-card accent-amber">
                <div class="an-kpi-label">Dampak Diskon</div>
                <div class="an-kpi-value">{{ $this->discountImpact }}%</div>
                <div class="an-kpi-sub">Potensi pendapatan yang hilang</div>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Pendapatan vs Diskon</h2><p>Perbandingan pendapatan dan potongan diskon per hari</p></div>
            </div>
            <div class="an-chart-wrap">
                @php $dt = $this->discountTrendChart; @endphp
                <canvas id="discountTrendChart" class="an-canvas"
                    data-labels="{{ json_encode($dt['labels']) }}"
                    data-values="{{ json_encode($dt['revenue']) }}"
                    data-values2="{{ json_encode($dt['discount']) }}"
                    data-type="dual-line"
                    data-color="teal"
                ></canvas>
            </div>
        </div>

        <div class="an-panel">
            <div class="an-panel-head">
                <div><h2>Produk Paling Menguntungkan</h2><p>Produk dengan pendapatan bersih tertinggi setelah diskon</p></div>
            </div>
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Terjual</th>
                            <th>Pendapatan Kotor</th>
                            <th>Diskon</th>
                            <th>Pendapatan Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->topProfitableProducts as $i => $item)
                        <tr>
                            <td>
                                @if($i < 3)
                                    <span class="an-rank an-rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                                @else
                                    <span class="an-rank-plain">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="an-product-cell">
                                    <span class="an-product-avatar">{{ strtoupper(substr($item->product_name, 0, 2)) }}</span>
                                    <span>{{ $item->product_name }}</span>
                                </div>
                            </td>
                            <td>{{ number_format($item->total_sold) }}</td>
                            <td>Rp {{ number_format($item->gross_revenue, 0, ',', '.') }}</td>
                            <td class="an-text-red">Rp {{ number_format($item->total_discount, 0, ',', '.') }}</td>
                            <td><strong>Rp {{ number_format($item->net_revenue, 0, ',', '.') }}</strong></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="an-empty">Belum ada data profit produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
(function () {
    'use strict';

    function cssVar(name, fallback) {
        const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return val || fallback;
    }

    function hexToRgba(hex, a) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r},${g},${b},${a})`;
    }

    function getPalette() {
        const isDark = document.documentElement.getAttribute('data-admin-theme') === 'dark';
        return {
            teal:   isDark ? '#2dd4bf' : '#0d9488',
            blue:   isDark ? '#60a5fa' : '#2563eb',
            green:  isDark ? '#4ade80' : '#16a34a',
            amber:  isDark ? '#fbbf24' : '#d97706',
            purple: isDark ? '#c084fc' : '#9333ea',
            red:    isDark ? '#f87171' : '#dc2626',
            multi:  isDark
                ? ['#2dd4bf','#60a5fa','#c084fc','#fbbf24','#4ade80','#f87171','#fb923c','#22d3ee','#f472b6','#a3e635']
                : ['#0d9488','#2563eb','#9333ea','#d97706','#16a34a','#dc2626','#ea580c','#0891b2','#db2777','#65a30d'],
        };
    }

    function resolveColor(color) {
        const p = getPalette();
        return p[color] || p.teal;
    }

    function gridColor()    { return cssVar('--admin-border', 'rgba(148,163,184,0.15)'); }
    function mutedColor()   { return cssVar('--admin-muted',  '#94a3b8'); }
    function panelBgColor() { return cssVar('--admin-panel',  '#ffffff'); }

    function fmtVal(val) {
        if (val >= 1e9) return (val / 1e9).toFixed(1) + 'B';
        if (val >= 1e6) return (val / 1e6).toFixed(1) + 'M';
        if (val >= 1e3) return (val / 1e3).toFixed(0) + 'K';
        return val.toFixed(0);
    }

    /* ── Ukuran Canvas ─────────────────────────────────────────── */
    function setCanvasSize(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const w   = canvas.offsetWidth || canvas.parentElement?.offsetWidth || 720;
        // Ambil tinggi dari CSS, fallback ke 280
        const h   = canvas.getBoundingClientRect().height || parseInt(getComputedStyle(canvas).height) || 280;
        canvas.width  = w * dpr;
        canvas.height = h * dpr;
        canvas.getContext('2d').scale(dpr, dpr);
        canvas._logicalW = w;
        canvas._logicalH = h;
    }

    /* ── Tampilan Kosong (tidak ada data) ──────────────────────── */
    function renderEmptyState(canvas) {
        const ctx = canvas.getContext('2d');
        const W   = canvas._logicalW;
        const H   = canvas._logicalH || 280;

        ctx.clearRect(0, 0, W, H);

        // Garis grid tipis agar panel tidak terasa kosong total
        ctx.strokeStyle = gridColor();
        ctx.lineWidth   = 1;
        for (let i = 0; i <= 4; i++) {
            const y = 20 + ((H - 60) / 4) * i;
            ctx.beginPath();
            ctx.moveTo(70, y);
            ctx.lineTo(W - 20, y);
            ctx.stroke();
        }

        // Ikon dan teks kosong di tengah
        ctx.fillStyle    = mutedColor();
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.font         = '14px system-ui';
        ctx.fillText('Belum ada data untuk periode ini', W / 2, H / 2);
    }

    /* ── Grafik Garis ──────────────────────────────────────────── */
    function renderLineChart(canvas, labels, values, color) {
        const ctx = canvas.getContext('2d');
        const W   = canvas._logicalW;
        const H   = canvas._logicalH;
        const pad = { top: 20, right: 20, bottom: 40, left: 70 };
        const cW  = W - pad.left - pad.right;
        const cH  = H - pad.top  - pad.bottom;
        const max = Math.max(...values, 1);

        ctx.clearRect(0, 0, W, H);

        ctx.strokeStyle = gridColor();
        ctx.lineWidth   = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (cH / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
            ctx.fillStyle = mutedColor(); ctx.font = '11px system-ui'; ctx.textAlign = 'right';
            ctx.fillText(fmtVal(max - (max / 4) * i), pad.left - 8, y + 4);
        }

        if (!values.length) return;

        const pts = values.map((v, i) => ({
            x: pad.left + (cW / Math.max(values.length - 1, 1)) * i,
            y: pad.top + cH - (v / max) * cH,
        }));

        const c    = resolveColor(color);
        const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + cH);
        grad.addColorStop(0, hexToRgba(c, 0.2));
        grad.addColorStop(1, hexToRgba(c, 0.01));

        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        pts.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(pts[pts.length - 1].x, pad.top + cH);
        ctx.lineTo(pts[0].x, pad.top + cH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        ctx.beginPath();
        ctx.strokeStyle = c; ctx.lineWidth = 2.5; ctx.lineJoin = 'round';
        ctx.moveTo(pts[0].x, pts[0].y);
        pts.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.stroke();

        pts.forEach(p => {
            ctx.beginPath(); ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = c; ctx.fill();
            ctx.strokeStyle = panelBgColor(); ctx.lineWidth = 2; ctx.stroke();
        });

        const step = Math.max(1, Math.ceil(labels.length / 10));
        ctx.fillStyle = mutedColor(); ctx.font = '11px system-ui'; ctx.textAlign = 'center';
        labels.forEach((l, i) => {
            if (i % step === 0 || i === labels.length - 1)
                ctx.fillText(l, pts[i].x, H - 10);
        });
    }

    /* ── Grafik Dua Garis ──────────────────────────────────────── */
    function renderDualLine(canvas, labels, v1, v2) {
        const ctx = canvas.getContext('2d');
        const W   = canvas._logicalW;
        const H   = canvas._logicalH;
        const pad = { top: 28, right: 20, bottom: 40, left: 70 };
        const cW  = W - pad.left - pad.right;
        const cH  = H - pad.top  - pad.bottom;
        const max = Math.max(...v1, ...v2, 1);
        const pal = getPalette();

        ctx.clearRect(0, 0, W, H);

        ctx.strokeStyle = gridColor(); ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (cH / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
            ctx.fillStyle = mutedColor(); ctx.font = '11px system-ui'; ctx.textAlign = 'right';
            ctx.fillText(fmtVal(max - (max / 4) * i), pad.left - 8, y + 4);
        }

        function drawLine(values, c) {
            if (!values.length) return [];
            const pts = values.map((v, i) => ({
                x: pad.left + (cW / Math.max(values.length - 1, 1)) * i,
                y: pad.top + cH - (v / max) * cH,
            }));
            ctx.beginPath(); ctx.strokeStyle = c; ctx.lineWidth = 2.5; ctx.lineJoin = 'round';
            ctx.moveTo(pts[0].x, pts[0].y);
            pts.forEach(p => ctx.lineTo(p.x, p.y));
            ctx.stroke();
            pts.forEach(p => {
                ctx.beginPath(); ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                ctx.fillStyle = c; ctx.fill();
            });
            return pts;
        }

        const pts1 = drawLine(v1, pal.teal);
        drawLine(v2, pal.red);

        // Legenda
        const lx = pad.left;
        ctx.fillStyle = pal.teal; ctx.fillRect(lx,      9, 14, 3);
        ctx.fillStyle = pal.red;  ctx.fillRect(lx + 90, 9, 14, 3);
        ctx.fillStyle = mutedColor(); ctx.font = '11px system-ui'; ctx.textAlign = 'left';
        ctx.fillText('Pendapatan', lx + 18,  13);
        ctx.fillText('Diskon',     lx + 108, 13);

        if (!pts1.length) return;
        const step = Math.max(1, Math.ceil(labels.length / 10));
        ctx.textAlign = 'center';
        labels.forEach((l, i) => {
            if (i % step === 0 || i === labels.length - 1)
                ctx.fillText(l, pts1[i].x, H - 10);
        });
    }

    /* ── Grafik Batang ─────────────────────────────────────────── */
    function renderBarChart(canvas, labels, values, color) {
        const ctx     = canvas.getContext('2d');
        const W       = canvas._logicalW;
        const H       = canvas._logicalH;
        const pad     = { top: 20, right: 20, bottom: 50, left: 70 };
        const cW      = W - pad.left - pad.right;
        const cH      = H - pad.top  - pad.bottom;
        const max     = Math.max(...values, 1);
        const gap     = cW / Math.max(values.length, 1);
        const barW    = Math.max(4, gap * 0.65);
        const isMulti = color === 'multi';
        const pal     = getPalette();

        ctx.clearRect(0, 0, W, H);

        ctx.strokeStyle = gridColor(); ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.top + (cH / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
            ctx.fillStyle = mutedColor(); ctx.font = '11px system-ui'; ctx.textAlign = 'right';
            ctx.fillText(fmtVal(max - (max / 4) * i), pad.left - 8, y + 4);
        }

        values.forEach((v, i) => {
            const x    = pad.left + gap * i + (gap - barW) / 2;
            const barH = (v / max) * cH;
            const y    = pad.top + cH - barH;
            const c    = isMulti ? pal.multi[i % pal.multi.length] : resolveColor(color);
            const r    = Math.min(5, barW / 2);

            ctx.fillStyle = c;
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + barW - r, y);
            ctx.quadraticCurveTo(x + barW, y, x + barW, y + r);
            ctx.lineTo(x + barW, y + barH);
            ctx.lineTo(x, y + barH);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = mutedColor(); ctx.font = '10px system-ui'; ctx.textAlign = 'center';
            const label = labels[i] || '';
            ctx.save();
            ctx.translate(x + barW / 2, pad.top + cH + 8);
            if (labels.length > 8) { ctx.rotate(-Math.PI / 4); ctx.textAlign = 'right'; }
            ctx.fillText(label.length > 12 ? label.slice(0, 11) + '…' : label, 0, 0);
            ctx.restore();
        });
    }

    /* ── Inisialisasi ──────────────────────────────────────────── */
    const observers = new Map();

    function drawCanvas(canvas) {
        setCanvasSize(canvas);

        const labels  = JSON.parse(canvas.dataset.labels  || '[]');
        const values  = JSON.parse(canvas.dataset.values  || '[]');
        const values2 = JSON.parse(canvas.dataset.values2 || '[]');
        const type    = canvas.dataset.type  || 'line';
        const color   = canvas.dataset.color || 'teal';

        // Periksa apakah ada data yang bernilai lebih dari nol
        const hasData = values.some(v => v > 0) || values2.some(v => v > 0);

        if (!hasData) {
            renderEmptyState(canvas);
            return;
        }

        if      (type === 'line')      renderLineChart(canvas, labels, values, color);
        else if (type === 'bar')       renderBarChart(canvas, labels, values, color);
        else if (type === 'dual-line') renderDualLine(canvas, labels, values, values2);
    }

    function initCanvas(canvas) {
        if (observers.has(canvas)) {
            observers.get(canvas).disconnect();
            observers.delete(canvas);
        }

        const ro = new ResizeObserver(() => {
            requestAnimationFrame(() => drawCanvas(canvas));
        });
        ro.observe(canvas);
        observers.set(canvas, ro);

        requestAnimationFrame(() => drawCanvas(canvas));
    }

    function initCharts() {
        document.querySelectorAll('.an-canvas').forEach(initCanvas);
    }

    // Re-render semua grafik saat tema (gelap/terang) berubah
    function observeThemeChange() {
        const mo = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                if (m.attributeName === 'data-admin-theme') {
                    requestAnimationFrame(initCharts);
                }
            });
        });
        mo.observe(document.documentElement, { attributes: true });
    }

    document.addEventListener('DOMContentLoaded',   () => { initCharts(); observeThemeChange(); });
    document.addEventListener('livewire:navigated', initCharts);
    document.addEventListener('livewire:updated',   () => setTimeout(initCharts, 60));
})();
</script>
@endpush