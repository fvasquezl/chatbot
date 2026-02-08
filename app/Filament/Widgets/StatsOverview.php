<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Revenue', '$'.number_format(Order::query()->sum('total'), 2))
                ->description('Total revenue')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('Orders', (string) Order::query()->count())
                ->description('Total orders')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
            Stat::make('Products', (string) Product::query()->count())
                ->description('Total products')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),
            Stat::make('Customers', (string) User::query()->count())
                ->description('Total customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
