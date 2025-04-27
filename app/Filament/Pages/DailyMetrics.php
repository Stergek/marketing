<?php

namespace App\Filament\Pages;

use App\Jobs\SyncMetaCampaigns;
use App\Models\DailyMetric;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
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

        // Check if dates are stored in the session or query string
        $this->startDate = $this->startDate ?? Session::get('daily_metrics_start_date') ?? now()->subDays(7)->toDateString();
        $this->endDate = $this->endDate ?? Session::get('daily_metrics_end_date') ?? now()->toDateString();

        // Debug: Log session and query string dates
        Log::info("Debug: Session and query string dates for DailyMetrics", [
            'session_start_date' => Session::get('daily_metrics_start_date'),
            'session_end_date' => Session::get('daily_metrics_end_date'),
            'query_start_date' => $this->startDate,
            'query_end_date' => $this->endDate,
        ]);

        // Initialize the filter state with the selected dates
        $this->tableFilters['date_range']['start_date'] = $this->startDate;
        $this->tableFilters['date_range']['end_date'] = $this->endDate;

        Session::put('daily_metrics_start_date', $this->startDate);
        Session::put('daily_metrics_end_date', $this->endDate);

        Log::info("DailyMetrics page loaded", [
            'ad_account_id' => $this->adAccountId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
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

        // Check for missing or outdated dates and dispatch sync jobs
        $metrics = DailyMetric::where('ad_account_id', $this->adAccountId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        $dateRange = CarbonPeriod::create($start, $end);
        $forceSync = false;
        foreach ($dateRange as $date) {
            $dateString = $date->toDateString();
            $metric = $metrics->firstWhere('date', $dateString);

            // If no record exists, dispatch a sync job
            if (!$metric) {
                Log::info("Dispatching sync job for missing date {$dateString} on DailyMetrics page.");
                SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                continue;
            }

            // Check if the data is outdated based on sync rules
            $isToday = $date->isSameDay(now());
            if ($isToday) {
                $lastSyncTime = Carbon::parse($metric->last_synced_at);
                $hoursSinceLastSync = $lastSyncTime->diffInHours(now());
                if ($hoursSinceLastSync >= 1) {
                    Log::info("Dispatching sync job for today {$dateString} on DailyMetrics page due to cooldown.");
                    SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                }
            } else {
                $endOfSelectedDate = $date->copy()->endOfDay();
                $lastSyncTime = Carbon::parse($metric->last_synced_at);
                if ($lastSyncTime->lessThan($endOfSelectedDate)) {
                    Log::info("Dispatching sync job for date {$dateString} on DailyMetrics page due to outdated data.");
                    SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                }
            }
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
                    ->sortable(),
                TextColumn::make('spend')
                    ->label('Total Spent')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('cpc')
                    ->label('Avg CPC')
                    ->money('usd')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->sortable(),
                TextColumn::make('roas')
                    ->label('ROAS')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    }),
                TextColumn::make('cpm')
                    ->label('CPM')
                    ->money('usd')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state <= 10) return 'success';
                        if ($state <= 20) return 'warning';
                        return 'danger';
                    }),
                TextColumn::make('ctr')
                    ->label('CTR (%)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default($this->startDate)
                            ->maxDate(now())
                            ->displayFormat('d-m-Y')
                            ->format('Y-m-d')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $this->updateDateRange($state, $get('end_date'), $set);
                            }),
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default($this->endDate)
                            ->maxDate(now())
                            ->displayFormat('d-m-Y')
                            ->format('Y-m-d')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $this->updateDateRange($get('start_date'), $state, $set);
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            Log::info("Applying date range filter to query", [
                                'start_date' => $data['start_date'],
                                'end_date' => $data['end_date'],
                            ]);
                            $query->whereBetween('date', [$data['start_date'], $data['end_date']]);
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            return 'Date Range: ' . Carbon::parse($data['start_date'])->format('d-m-Y') . ' to ' . Carbon::parse($data['end_date'])->format('d-m-Y');
                        }
                        return null;
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->actions([])
            ->emptyStateHeading('No metrics found')
            ->emptyStateDescription('No data for the selected date range. A sync job may have been dispatched; please refresh the page in a moment.');
    }

    protected function updateDateRange(?string $startDate, ?string $endDate, callable $set)
    {
        Log::info("Date range filter updated", [
            'new_start_date' => $startDate,
            'new_end_date' => $endDate,
            'old_start_date' => $this->startDate,
            'old_end_date' => $this->endDate,
            'table_filters_before' => $this->tableFilters,
        ]);

        // Skip if either date is not set
        if (!$startDate || !$endDate) {
            Log::info("One or both dates not set, skipping table reset", [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            return;
        }

        // Validate date format
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($datePattern, $startDate) || !preg_match($datePattern, $endDate)) {
            Log::error("Invalid date format", [
                'start_date' => $startDate,
                'end_date' => $endDate,
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
        $set('tableFilters.date_range.start_date', $this->startDate);
        $set('tableFilters.date_range.end_date', $this->endDate);
        $this->tableFilters['date_range']['start_date'] = $this->startDate;
        $this->tableFilters['date_range']['end_date'] = $this->endDate;
        Session::put('daily_metrics_start_date', $this->startDate);
        Session::put('daily_metrics_end_date', $this->endDate);

        Log::info("Resetting table for date range", [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);
        $this->resetTable();

        Log::info("Table filters updated", [
            'new_start_date' => $this->startDate,
            'new_end_date' => $this->endDate,
            'table_filters_after' => $this->tableFilters,
        ]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->date;
    }
}