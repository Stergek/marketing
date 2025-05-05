<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\DailyMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class KeyMetricsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Key Metrics Summary';

    protected static ?string $pollingInterval = null;

    public function mount(): void
    {
        // No need to compute stats here; getStats() will handle it
    }

    protected function getStats(): array
    {
        Log::info('Filters in KeyMetricsOverview before computing stats', [
            'filters' => $this->filters,
        ]);

        Log::info('Computing stats for KeyMetricsOverview', [
            'start_date' => $this->filters['start_date'] ?? 'not set',
            'end_date' => $this->filters['end_date'] ?? 'not set',
        ]);

        try {
            $startDate = Carbon::parse($this->filters['start_date'] ?? now()->subDays(7));
            $endDate = Carbon::parse($this->filters['end_date'] ?? now());
        } catch (\Exception $e) {
            Log::error('Error parsing dates in KeyMetricsOverview', [
                'start_date' => $this->filters['start_date'],
                'end_date' => $this->filters['end_date'],
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultStats();
        }

        $daysDifference = $startDate->diffInDays($endDate) + 1;

        $previousEndDate = $startDate->copy()->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays($daysDifference - 1);

        $metricsQuery = DailyMetric::query()
            ->whereBetween('date', [$startDate, $endDate]);

        $metrics = $metricsQuery->get();

        $previousMetricsQuery = DailyMetric::query()
            ->whereBetween('date', [$previousStartDate, $previousEndDate]);

        $previousMetrics = $previousMetricsQuery->get();

        $totalSpend = $metrics->sum('spend') ?? 0;
        $averageCpc = $metrics->avg('cpc') ?? 0;
        $totalImpressions = $metrics->sum('impressions') ?? 0;
        $totalClicks = $metrics->sum('clicks') ?? 0;
        $averageRoas = $metrics->avg('roas') ?? 0;
        $averageCtr = $metrics->avg('ctr') ?? 0;

        $previousTotalSpend = $previousMetrics->sum('spend') ?? 0;
        $previousAverageCpc = $previousMetrics->avg('cpc') ?? 0;
        $previousTotalImpressions = $previousMetrics->sum('impressions') ?? 0;
        $previousTotalClicks = $previousMetrics->sum('clicks') ?? 0;
        $previousAverageRoas = $previousMetrics->avg('roas') ?? 0;
        $previousAverageCtr = $previousMetrics->avg('ctr') ?? 0;

        $spendTrend = $this->calculateTrend($totalSpend, $previousTotalSpend);
        $cpcTrend = $this->calculateTrend($averageCpc, $previousAverageCpc);
        $impressionsTrend = $this->calculateTrend($totalImpressions, $previousTotalImpressions);
        $clicksTrend = $this->calculateTrend($totalClicks, $previousTotalClicks);
        $roasTrend = $this->calculateTrend($averageRoas, $previousAverageRoas);
        $ctrTrend = $this->calculateTrend($averageCtr, $previousAverageCtr);

        $stats = [
            Stat::make('Total Spend', '$' . number_format($totalSpend, 2))
                ->description($spendTrend >= 0 ? "+{$spendTrend}%" : "{$spendTrend}%")
                ->descriptionIcon($spendTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($spendTrend >= 0 ? 'success' : 'danger'),
            Stat::make('Average CPC', '$' . number_format($averageCpc, 2))
                ->description($cpcTrend >= 0 ? "+{$cpcTrend}%" : "{$cpcTrend}%")
                ->descriptionIcon($cpcTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($cpcTrend >= 0 ? 'success' : 'danger'),
            Stat::make('Total Impressions', number_format($totalImpressions))
                ->description($impressionsTrend >= 0 ? "+{$impressionsTrend}%" : "{$impressionsTrend}%")
                ->descriptionIcon($impressionsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($impressionsTrend >= 0 ? 'success' : 'danger'),
            Stat::make('Total Clicks', number_format($totalClicks))
                ->description($clicksTrend >= 0 ? "+{$clicksTrend}%" : "{$clicksTrend}%")
                ->descriptionIcon($clicksTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($clicksTrend >= 0 ? 'success' : 'danger'),
            Stat::make('Average ROAS', number_format($averageRoas, 2))
                ->description($roasTrend >= 0 ? "+{$roasTrend}%" : "{$roasTrend}%")
                ->descriptionIcon($roasTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($roasTrend >= 0 ? 'success' : 'danger'),
            Stat::make('Average CTR', number_format($averageCtr, 2) . '%')
                ->description($ctrTrend >= 0 ? "+{$ctrTrend}%" : "{$ctrTrend}%")
                ->descriptionIcon($ctrTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ctrTrend >= 0 ? 'success' : 'danger'),
        ];

        Log::info('Computed stats for KeyMetricsOverview', [
            'total_spend' => $totalSpend,
            'average_cpc' => $averageCpc,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'average_roas' => $averageRoas,
            'average_ctr' => $averageCtr,
        ]);

        return $stats;
    }

    protected function getDefaultStats(): array
    {
        return [
            Stat::make('Total Spend', '$0.00')
                ->description('0%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Average CPC', '$0.00')
                ->description('0%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Impressions', '0')
                ->description('0%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Clicks', '0')
                ->description('0%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Average ROAS', '0.00')
                ->description('0%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Average CTR', '0.00%')
                ->description('0%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }

    protected function calculateTrend(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected int | string | array $columnSpan = 12;

    protected function getColumns(): int
    {
        return 4;
    }

}