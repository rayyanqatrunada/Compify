<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Grafik Penjualan Dummy';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $month) => now()->subMonths($month));

        $sales = $months->map(function (Carbon $month) {
            return (int) Order::query()
                ->whereYear('ordered_at', $month->year)
                ->whereMonth('ordered_at', $month->month)
                ->sum('total');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $sales->values()->all(),
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, .18)',
                    'fill' => true,
                    'tension' => .42,
                ],
            ],
            'labels' => $months->map(fn (Carbon $month) => $month->format('M Y'))->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
