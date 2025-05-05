<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TestMetricsOverview;
use App\Filament\Widgets\TestTrendsChart;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class Test extends Page
{
    protected static string $view = 'filament.pages.test';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $title = 'Test Page';

    protected static ?string $slug = 'test';

    protected $queryString = [
        'filters[date_range_select]' => ['except' => 'Today'],
        'filters[start_date]' => ['except' => ''],
        'filters[end_date]' => ['except' => ''],
    ];

    public array $filters = [];

    public string $activeTab = 'Today';

    public function mount(): void
    {
        // Initialize filters if not set
        $this->filters = array_merge([
            'date_range_select' => 'Today',
            'start_date' => now()->startOfDay()->toDateString(),
            'end_date' => now()->endOfDay()->toDateString(),
        ], $this->filters ?? []);

        $this->activeTab = $this->filters['date_range_select'];

        Log::info('Mounting Test Page', [
            'filters' => $this->filters,
            'active_tab' => $this->activeTab,
        ]);

        $this->updateDateRange();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filters')
                ->icon('heroicon-m-funnel')
                ->form([
                    Forms\Components\Select::make('date_range_select')
                        ->label('Date Range')
                        ->options([
                            'Today' => 'Today',
                            'Yesterday' => 'Yesterday',
                            'This Week' => 'This Week',
                            'Last Week' => 'Last Week',
                            'This Month' => 'This Month',
                            'Last Month' => 'Last Month',
                            'Custom' => 'Custom',
                        ])
                        ->default('Today')
                        ->selectablePlaceholder(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $this->activeTab = $state;
                            $this->updateDateRange();
                            $set('start_date', $this->filters['start_date']);
                            $set('end_date', $this->filters['end_date']);
                        }),
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->default(now()->subDays(7))
                        ->required()
                        ->hidden(fn ($get) => $get('date_range_select') !== 'Custom')
                        ->live(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->default(now())
                        ->required()
                        ->hidden(fn ($get) => $get('date_range_select') !== 'Custom')
                        ->live(),
                ])
                ->action(function (array $data) {
                    $this->filters = $data;
                    $this->updateDateRange();
                    Log::info('Filters applied from modal in Test Page', [
                        'filters' => $this->filters,
                    ]);
                }),
        ];
    }

    protected function updateDateRange(): void
    {
        $dateRange = $this->filters['date_range_select'] ?? 'Today';
        $today = now()->toDateString();

        if ($dateRange !== 'Custom') {
            $dates = match ($dateRange) {
                'Today' => [
                    'start_date' => now()->startOfDay()->toDateString(),
                    'end_date' => now()->endOfDay()->toDateString(),
                ],
                'Yesterday' => [
                    'start_date' => now()->subDay()->startOfDay()->toDateString(),
                    'end_date' => now()->subDay()->endOfDay()->toDateString(),
                ],
                'This Week' => [
                    'start_date' => now()->startOfWeek()->toDateString(),
                    'end_date' => $today,
                ],
                'Last Week' => [
                    'start_date' => now()->subWeek()->startOfWeek()->toDateString(),
                    'end_date' => now()->subWeek()->endOfWeek()->toDateString(),
                ],
                'This Month' => [
                    'start_date' => now()->startOfMonth()->toDateString(),
                    'end_date' => $today,
                ],
                'Last Month' => [
                    'start_date' => now()->subMonth()->startOfMonth()->toDateString(),
                    'end_date' => now()->subMonth()->endOfMonth()->toDateString(),
                ],
                default => [
                    'start_date' => now()->startOfDay()->toDateString(),
                    'end_date' => now()->endOfDay()->toDateString(),
                ],
            };

            $this->filters['start_date'] = $dates['start_date'];
            $this->filters['end_date'] = $dates['end_date'];
        }

        Log::info('Updated date range in Test Page', [
            'date_range' => $dateRange,
            'start_date' => $this->filters['start_date'],
            'end_date' => $this->filters['end_date'],
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            TestMetricsOverview::class,
            TestTrendsChart::class,
        ];
    }
}