<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TestMetricsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Test Metrics Overview';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 2; // Full width

    protected function getStats(): array
    {
        // Use static data for UI testing
        $totalSpend = 1500.00;
        $averageCpc = 0.60;
        $averageRoas = 3.50;
        $averageCtr = 1.25;

        // Static trends for demonstration
        $spendTrend = 10.5;
        $cpcTrend = -5.2;
        $roasTrend = 12.7;
        $ctrTrend = -3.1;

        return [
            Stat::make('Total Spend', '$' . number_format($totalSpend, 2))
                ->description($spendTrend >= 0 ? "+{$spendTrend}%" : "{$spendTrend}%")
                ->descriptionIcon($spendTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($spendTrend >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'w-1/4 min-w-0 p-1 text-xs',
                ]),
            Stat::make('Average CPC', '$' . number_format($averageCpc, 2))
                ->description($cpcTrend >= 0 ? "+{$cpcTrend}%" : "{$cpcTrend}%")
                ->descriptionIcon($cpcTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($cpcTrend >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'w-1/4 min-w-0 p-1 text-xs',
                ]),
            Stat::make('Average ROAS', number_format($averageRoas, 2))
                ->description($roasTrend >= 0 ? "+{$roasTrend}%" : "{$roasTrend}%")
                ->descriptionIcon($roasTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($roasTrend >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'w-1/4 min-w-0 p-1 text-xs',
                ]),
            Stat::make('Average CTR', number_format($averageCtr, 2) . '%')
                ->description($ctrTrend >= 0 ? "+{$ctrTrend}%" : "{$ctrTrend}%")
                ->descriptionIcon($ctrTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ctrTrend >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'w-1/4 min-w-0 p-1 text-xs',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4; // 4 cards in one row
    }
}