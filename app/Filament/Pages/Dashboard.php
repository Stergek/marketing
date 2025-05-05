<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Form;
use Filament\Forms;
use App\Filament\Widgets\KeyMetricsOverview;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public string $activeTab = 'Today';

    protected $queryString = [
        'filters[date_range_select]' => ['except' => 'Today'],
        'filters[start_date]' => ['except' => ''],
        'filters[end_date]' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Log initial filter state from query string
        Log::info('Initial filters from query string in Dashboard', [
            'filters' => $this->filters,
        ]);

        // Initialize filters if not set
        $this->filters = array_merge([
            'date_range_select' => 'Today',
            'start_date' => now()->startOfDay()->toDateString(),
            'end_date' => now()->endOfDay()->toDateString(),
        ], $this->filters ?? []);

        $this->activeTab = $this->filters['date_range_select'];

        Log::info('Mounting Dashboard', [
            'filters' => $this->filters,
            'active_tab' => $this->activeTab,
        ]);

        $this->updateDateRange();
        $this->form->fill($this->filters);
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->afterStateUpdated(function ($state) {
                        $this->activeTab = $state;
                        $this->updateDateRange();
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
            ]);
    }

    protected function updateDateRange(): void
    {
        $dateRange = $this->filters['date_range_select'] ?? 'Today';
        $today = now()->toDateString();

        $oldFilters = $this->filters;

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

        // Only update the form if the filters have actually changed
        if ($oldFilters['start_date'] !== $this->filters['start_date'] ||
            $oldFilters['end_date'] !== $this->filters['end_date']) {
            $this->form->fill($this->filters);
        }

        Log::info('Updated date range in Dashboard', [
            'date_range' => $dateRange,
            'start_date' => $this->filters['start_date'],
            'end_date' => $this->filters['end_date'],
        ]);
    }

    public function getWidgets(): array
    {
        return [
            KeyMetricsOverview::class,
        ];
    }
}