dailymetrics
<?php

namespace App\Filament\Pages;

use App\Jobs\SyncMetaCampaigns;
use App\Models\DailyMetric;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Filament\Tables\Table as FilamentTable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DailyMetrics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.daily-metrics';
    protected static ?string $navigationLabel = 'Daily Metrics';
    protected static ?string $title = 'Daily Metrics';

    public $startDate;
    public $endDate;
    public $adAccountId;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'tableSortColumn',
        'tableSortDirection',
        'tableFilters',
    ];

    public function mount()
    {
        $setting = Setting::first();
        $this->adAccountId = $setting ? $setting->ad_account_id : null;

        if (empty($this->adAccountId)) {
            Notification::make()
                ->title('Warning')
                ->body('Ad Account ID is not set. Please configure it in the Settings page.')
                ->warning()
                ->send();
        }

        // Load from session or query string
        $this->startDate = $this->startDate ?? Session::get('daily_metrics_start_date') ?? now()->subDays(7)->toDateString();
        $this->endDate = $this->endDate ?? Session::get('daily_metrics_end_date') ?? now()->toDateString();
        $sessionRange = Session::get('daily_metrics_range', 'last_7_days');

        // Initialize filter state
        $this->tableFilters['date_range'] = [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'range' => $sessionRange,
        ];

        // Validate and adjust range based on dates
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $range = $sessionRange;

        // Only override session range if dates don't match
        if ($range === 'last_7_days' && !($start->eq(now()->subDays(7)->startOfDay()) && $end->eq(now()->endOfDay()))) {
            $range = 'custom';
        } elseif ($range === 'last_30_days' && !($start->eq(now()->subDays(30)->startOfDay()) && $end->eq(now()->endOfDay()))) {
            $range = 'custom';
        } elseif ($range === 'last_month' && !($start->eq(now()->subMonth()->startOfMonth()) && $end->eq(now()->subMonth()->endOfMonth()))) {
            $range = 'custom';
        } elseif ($range === 'this_month' && !($start->eq(now()->startOfMonth()) && $end->eq(now()->endOfDay()))) {
            $range = 'custom';
        }

        // Update filter state
        $this->tableFilters['date_range']['range'] = $range;
        Session::put('daily_metrics_range', $range);

        // Debug: Log session and query string
        Log::info("Debug: Initializing DailyMetrics", [
            'session_start_date' => Session::get('daily_metrics_start_date'),
            'session_end_date' => Session::get('daily_metrics_end_date'),
            'session_range' => Session::get('daily_metrics_range'),
            'query_start_date' => $this->startDate,
            'query_end_date' => $this->endDate,
            'calculated_range' => $range,
            'table_filters' => $this->tableFilters,
        ]);

        Session::put('daily_metrics_start_date', $this->startDate);
        Session::put('daily_metrics_end_date', $this->endDate);

        Log::info("DailyMetrics page loaded", [
            'ad_account_id' => $this->adAccountId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'range' => $range,
            'table_filters' => $this->tableFilters,
        ]);
    }

    public function table(FilamentTable $table): FilamentTable
    {
        if (empty($this->adAccountId)) {
            return $table
                ->query(DailyMetric::query())
                ->columns([])
                ->filters([])
                ->emptyStateHeading('Configuration Required')
                ->emptyStateDescription('Please configure the Ad Account ID in the Settings page.');
        }

        // Validate dates
        try {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
        } catch (\Exception $e) {
            Log::error("DailyMetrics Page: Invalid date format provided", [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'error' => $e->getMessage(),
            ]);
            Notification::make()
                ->title('Error')
                ->body('Invalid date format. Please use DD-MM-YYYY.')
                ->danger()
                ->send();
            return $table->query(DailyMetric::query())->columns([])->filters([]);
        }

        if ($start->gt($end)) {
            Notification::make()
                ->title('Error')
                ->body('Start date cannot be after end date.')
                ->danger()
                ->send();
            return $table->query(DailyMetric::query())->columns([])->filters([]);
        }

        // Check for missing dates and dispatch sync jobs
        $metrics = DailyMetric::where('ad_account_id', $this->adAccountId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        $dateRange = CarbonPeriod::create($start, $end);
        $forceSync = false;
        foreach ($dateRange as $date) {
            $dateString = $date->toDateString();
            $metric = $metrics->firstWhere('date', $dateString);

            // If no record exists, dispatch a sync job (applies to all dates that were never synced)
            if (!$metric) {
                Log::info("Dispatching sync job for missing date {$dateString} on DailyMetrics page.");
                SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                continue;
            }

            // For yesterday, sync if last sync was before today at 03:00
            $isYesterday = $date->isSameDay(now()->subDay());
            if ($isYesterday) {
                $lastSyncTime = Carbon::parse($metric->last_synced_at);
                $syncCutoff = now()->startOfDay()->addHours(3); // Today at 03:00
                if ($lastSyncTime->lessThan($syncCutoff)) {
                    Log::info("Dispatching sync job for yesterday {$dateString} on DailyMetrics page because last sync was before today at 03:00.", [
                        'last_synced_at' => $lastSyncTime->toDateTimeString(),
                        'sync_cutoff' => $syncCutoff->toDateTimeString(),
                    ]);
                    SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                }
                continue;
            }

            // For today, sync only if last sync was more than 1 hour ago
            $isToday = $date->isSameDay(now());
            if ($isToday) {
                $lastSyncTime = Carbon::parse($metric->last_synced_at);
                $hoursSinceLastSync = $lastSyncTime->diffInHours(now());
                if ($hoursSinceLastSync >= 1) {
                    Log::info("Dispatching sync job for today {$dateString} on DailyMetrics page due to cooldown.");
                    SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                }
            }
            // For dates before yesterday, do not sync again if a record exists
        }

        // Debug: Log the query being executed
        $query = DailyMetric::query()
            ->where('ad_account_id', $this->adAccountId)
            ->whereBetween('date', [$this->startDate, $this->endDate]);
        Log::info("Executing table query", [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'ad_account_id' => $this->adAccountId,
            'query' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('spend')
                    ->label('Total Spent')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cpc')
                    ->label('Avg CPC')
                    ->money('usd')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('roas')
                    ->label('ROAS')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(),
                TextColumn::make('cpm')
                    ->label('CPM')
                    ->money('usd')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state <= 10) return 'success';
                        if ($state <= 20) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ctr')
                    ->label('CTR (%)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('impressions')
                    ->label('Impressions')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('clicks')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make()->schema([
                            Select::make('range')
                                ->label('Date Range')
                                ->options([
                                    'last_7_days' => 'Last 7 Days',
                                    'last_30_days' => 'Last 30 Days',
                                    'last_month' => 'Last Month',
                                    'this_month' => 'This Month',
                                    'custom' => 'Custom',
                                ])
                                ->default($this->tableFilters['date_range']['range'] ?? 'last_7_days')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $startDate = null;
                                    $endDate = now()->toDateString();

                                    switch ($state) {
                                        case 'last_7_days':
                                            $startDate = now()->subDays(7)->toDateString();
                                            break;
                                        case 'last_30_days':
                                            $startDate = now()->subDays(30)->toDateString();
                                            break;
                                        case 'last_month':
                                            $startDate = now()->subMonth()->startOfMonth()->toDateString();
                                            $endDate = now()->subMonth()->endOfMonth()->toDateString();
                                            break;
                                        case 'this_month':
                                            $startDate = now()->startOfMonth()->toDateString();
                                            $endDate = now()->toDateString(); // Cap at today
                                            break;
                                        case 'custom':
                                            $startDate = $get('start_date') ?? now()->subDays(7)->toDateString();
                                            $endDate = $get('end_date') ?? now()->toDateString();
                                            break;
                                    }

                                    $set('start_date', $startDate);
                                    $set('end_date', $endDate);
                                    $set('range', $state);
                                    $this->tableFilters['date_range']['range'] = $state;
                                    $this->tableFilters['date_range']['start_date'] = $startDate;
                                    $this->tableFilters['date_range']['end_date'] = $endDate;

                                    $this->updateDateRange($startDate, $endDate, $state);

                                    Log::info("Select range updated", [
                                        'range' => $state,
                                        'start_date' => $startDate,
                                        'end_date' => $endDate,
                                        'table_filters' => $this->tableFilters,
                                    ]);
                                }),
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->default($this->startDate)
                                ->maxDate(now())
                                ->displayFormat('d-m-Y')
                                ->format('Y-m-d')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $set('range', 'custom');
                                    $this->tableFilters['date_range']['range'] = 'custom';
                                    $this->tableFilters['date_range']['start_date'] = $state;
                                    $this->updateDateRange($state, $get('end_date'), 'custom');

                                    Log::info("Start date updated, set to custom", [
                                        'start_date' => $state,
                                        'end_date' => $get('end_date'),
                                        'range' => 'custom',
                                        'table_filters' => $this->tableFilters,
                                    ]);
                                }),
                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->default($this->endDate)
                                ->maxDate(now())
                                ->displayFormat('d-m-Y')
                                ->format('Y-m-d')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $set('range', 'custom');
                                    $this->tableFilters['date_range']['range'] = 'custom';
                                    $this->tableFilters['date_range']['end_date'] = $state;
                                    $this->updateDateRange($get('start_date'), $state, 'custom');

                                    Log::info("End date updated, set to custom", [
                                        'start_date' => $get('start_date'),
                                        'end_date' => $state,
                                        'range' => 'custom',
                                        'table_filters' => $this->tableFilters,
                                    ]);
                                }),
                        ])->columns(1),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            Log::info("Applying date range filter to query", [
                                'start_date' => $data['start_date'],
                                'end_date' => $data['end_date'],
                                'range' => $data['range'],
                            ]);
                            $query->whereBetween('date', [$data['start_date'], $data['end_date']]);
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            $label = $data['range'] !== 'custom'
                                ? ucfirst(str_replace('_', ' ', $data['range']))
                                : 'Custom: ' . Carbon::parse($data['start_date'])->format('d-m-Y') . ' to ' . Carbon::parse($data['end_date'])->format('d-m-Y');
                            return 'Date Range: ' . $label;
                        }
                        return null;
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->actions([])
            ->emptyStateHeading('No metrics found')
            ->emptyStateDescription('No data for the selected date range. A sync job may have been dispatched; please refresh the page in a moment.');
    }

    protected function updateDateRange(?string $startDate, ?string $endDate, ?string $range)
    {
        Log::info("Date range filter updated", [
            'new_start_date' => $startDate,
            'new_end_date' => $endDate,
            'new_range' => $range,
            'old_start_date' => $this->startDate,
            'old_end_date' => $this->endDate,
            'table_filters_before' => $this->tableFilters,
        ]);

        // Skip if either date is not set
        if (!$startDate || !$endDate) {
            Log::info("One or both dates not set, skipping table reset", [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'range' => $range,
            ]);
            return;
        }

        // Validate date format
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($datePattern, $startDate) || !preg_match($datePattern, $endDate)) {
            Log::error("Invalid date format", [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'range' => $range,
            ]);
            Notification::make()
                ->title('Error')
                ->body('Invalid date format. Please use DD-MM-YYYY.')
                ->danger()
                ->send();
            return;
        }

        // Parse and validate dates
        try {
            $start = Carbon::parse($startDate, 'UTC');
            $end = Carbon::parse($endDate, 'UTC');

            if ($start->gt($end)) {
                Log::error("Start date is after end date", [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'range' => $range,
                ]);
                Notification::make()
                    ->title('Error')
                    ->body('Start date cannot be after end date.')
                    ->danger()
                    ->send();
                return;
            }
        } catch (\Exception $e) {
            Log::error("Failed to parse date range", [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'range' => $range,
                'error' => $e->getMessage(),
            ]);
            Notification::make()
                ->title('Error')
                ->body('Invalid date format. Please use DD-MM-YYYY.')
                ->danger()
                ->send();
            return;
        }

        // Update state and filters
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
        $this->tableFilters['date_range']['start_date'] = $this->startDate;
        $this->tableFilters['date_range']['end_date'] = $this->endDate;
        $this->tableFilters['date_range']['range'] = $range;

        Session::put('daily_metrics_start_date', $this->startDate);
        Session::put('daily_metrics_end_date', $this->endDate);
        Session::put('daily_metrics_range', $range);

        Log::info("Resetting table for date range", [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'range' => $range,
        ]);
        $this->resetTable();

        Log::info("Table filters updated", [
            'new_start_date' => $this->startDate,
            'new_end_date' => $endDate,
            'new_range' => $range,
            'table_filters_after' => $this->tableFilters,
        ]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->date;
    }
}