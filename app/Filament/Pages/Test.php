<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TestMetricsOverview;
use App\Filament\Widgets\TestTrendsChart;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
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
                    Grid::make(12) // 12-column grid
                        ->schema([
                            Select::make('date_range_select')
                                ->label('More Date Ranges')
                                ->options([
                                    'This Week' => 'This Week',
                                    'Last Week' => 'Last Week',
                                    'This Month' => 'This Month',
                                    'Last Month' => 'Last Month',
                                ])
                                ->placeholder('Select a date range')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $this->activeTab = $state;
                                        $this->updateDateRange();
                                        $set('date_range_select', $state);
                                    }
                                })
                                ->columnSpan(3), // Take 3 columns (left side)
                            Tabs::make('Filter Tabs')
                                ->tabs([
                                    Tabs\Tab::make('Today')
                                        ->icon('heroicon-m-calendar')
                                        ->schema([
                                            Forms\Components\Hidden::make('date_range_select')
                                                ->default('Today'),
                                        ])
                                        ->reactive()
                                        ->afterStateUpdated(function (callable $set) {
                                            $this->activeTab = 'Today';
                                            $set('date_range_select', 'Today');
                                            $this->updateDateRange();
                                        }),
                                    Tabs\Tab::make('Yesterday')
                                        ->icon('heroicon-m-calendar')
                                        ->schema([
                                            Forms\Components\Hidden::make('date_range_select')
                                                ->default('Yesterday'),
                                        ])
                                        ->reactive()
                                        ->afterStateUpdated(function (callable $set) {
                                            $this->activeTab = 'Yesterday';
                                            $set('date_range_select', 'Yesterday');
                                            $this->updateDateRange();
                                        }),
                                    Tabs\Tab::make('Custom Range')
                                        ->icon('heroicon-m-adjustments-horizontal')
                                        ->schema([
                                            Forms\Components\Hidden::make('date_range_select')
                                                ->default('Custom'),
                                            Forms\Components\DatePicker::make('start_date')
                                                ->label('Start Date')
                                                ->default(now()->subDays(7))
                                                ->required()
                                                ->live(),
                                            Forms\Components\DatePicker::make('end_date')
                                                ->label('End Date')
                                                ->default(now())
                                                ->required()
                                                ->live(),
                                        ])
                                        ->reactive()
                                        ->afterStateUpdated(function (callable $set) {
                                            $set('date_range_select', 'Custom');
                                            $this->activeTab = 'Custom';
                                        }),
                                ])
                                ->persistTabInQueryString('filter-tab')
                                ->columnSpan(9), // Take 9 columns (right side)
                        ]),
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