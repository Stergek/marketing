<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TestTrendsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Test Trends Chart';

    protected static ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Static data for demonstration
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Clicks',
                    'data' => [100, 200, 150, 300, 250, 400],
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                ],
            ],
        ];
    }
}