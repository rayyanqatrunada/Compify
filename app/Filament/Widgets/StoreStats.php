<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Produk', Product::count())
                ->description('Produk aktif dan draft')
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->chart([8, 12, 16, 14, 19, 22, 26])
                ->color('info'),
            Stat::make('Total User', User::count())
                ->description('Termasuk admin dan customer')
                ->descriptionIcon(Heroicon::Users)
                ->chart([2, 4, 5, 7, 9, 10, 11])
                ->color('success'),
            Stat::make('Total Kategori', Category::count())
                ->description('Kategori katalog Compify')
                ->descriptionIcon(Heroicon::Tag)
                ->chart([3, 3, 4, 4, 5, 5, 5])
                ->color('gray'),
            Stat::make('Penjualan Dummy', 'Rp '.number_format(Order::sum('total'), 0, ',', '.'))
                ->description('Akumulasi order seed')
                ->descriptionIcon(Heroicon::Banknotes)
                ->chart([4, 9, 7, 13, 15, 22, 30])
                ->color('warning'),
        ];
    }
}
