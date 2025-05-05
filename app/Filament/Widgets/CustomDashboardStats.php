<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomDashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue', '$5000.00')
                ->description('This month')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Total Orders', '120')
                ->description('This month')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('warning'),
            Stat::make('Conversion Rate', '3.5%')
                ->description('This month')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }
}