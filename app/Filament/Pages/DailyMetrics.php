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

        // Load from session or query string with defaults
        $this->startDate = $this->startDate ?? Session::get('daily_metrics_start_date') ?? now()->subDays(7)->toDateString();
        $this->endDate = $this->endDate ?? Session::get('daily_metrics_end_date') ?? now()->toDateString();
        $sessionRange = Session::get('daily_metrics_range', 'last_7_days');

        // Initialize filter state
        $this->tableFilters['date_range'] = [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'range' => $sessionRange,
        ];

        // Adjust range based on dates
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $range = $sessionRange;

        if ($range === 'last_7_days' && !($start->eq(now()->subDays(7)->startOfDay()) && $end->eq(now()->endOfDay()))) {
            $range = 'custom';
        } elseif ($range === 'last_30_days' && !($start->eq(now()->subDays(30)->startOfDay()) && $end->eq(now()->endOfDay()))) {
            $range = 'custom';
        } elseif ($range === 'last_month' && !($start->eq(now()->subMonth()->startOfMonth()) && $end->eq(now()->subMonth()->endOfMonth()))) {
            $range = 'custom';
        } elseif ($range === 'this_month' && !($start->eq(now()->startOfMonth()) && $end->eq(now()->endOfDay()))) {
            $range = 'custom';
        }

        $this->tableFilters['date_range']['range'] = $range;
        Session::put('daily_metrics_range', $range);
        Session::put('daily_metrics_start_date', $this->startDate);
        Session::put('daily_metrics_end_date', $this->endDate);
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

        // Check for missing or outdated data and dispatch sync jobs
        $metrics = DailyMetric::where('ad_account_id', $this->adAccountId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        $dateRange = CarbonPeriod::create($start, $end);
        $forceSync = false;
        foreach ($dateRange as $date) {
            $dateString = $date->toDateString();
            $metric = $metrics->firstWhere('date', $dateString);

            if (!$metric) {
                SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                continue;
            }

            $isYesterday = $date->isSameDay(now()->subDay());
            if ($isYesterday && Carbon::parse($metric->last_synced_at)->lessThan(now()->startOfDay()->addHours(3))) {
                SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
                continue;
            }

            $isToday = $date->isSameDay(now());
            if ($isToday && Carbon::parse($metric->last_synced_at)->diffInHours(now()) >= 1) {
                SyncMetaCampaigns::dispatch($dateString, $forceSync, 'ad_account');
            }
        }

        $query = DailyMetric::query()
            ->where('ad_account_id', $this->adAccountId)
            ->whereBetween('date', [$this->startDate, $this->endDate]);

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
                                            $endDate = now()->toDateString();
                                            break;
                                        case 'custom':
                                            $startDate = $get('start_date') ?? now()->subDays(7)->toDateString();
                                            $endDate = $get('end_date') ?? now()->toDateString();
                                            break;
                                    }

                                    $set('start_date', $startDate);
                                    $set('end_date', $endDate);
                                    $this->tableFilters['date_range']['range'] = $state;
                                    $this->tableFilters['date_range']['start_date'] = $startDate;
                                    $this->tableFilters['date_range']['end_date'] = $endDate;
                                    $this->updateDateRange($startDate, $endDate, $state);
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
                                }),
                        ])->columns(1),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
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
        if (!$startDate || !$endDate) return;

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->gt($end)) {
                Notification::make()
                    ->title('Error')
                    ->body('Start date cannot be after end date.')
                    ->danger()
                    ->send();
                return;
            }

            $this->startDate = $start->format('Y-m-d');
            $this->endDate = $end->format('Y-m-d');
            $this->tableFilters['date_range']['start_date'] = $this->startDate;
            $this->tableFilters['date_range']['end_date'] = $this->endDate;
            $this->tableFilters['date_range']['range'] = $range;

            Session::put('daily_metrics_start_date', $this->startDate);
            Session::put('daily_metrics_end_date', $this->endDate);
            Session::put('daily_metrics_range', $range);

            $this->resetTable();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Invalid date format. Please use DD-MM-YYYY.')
                ->danger()
                ->send();
        }
    }

    public function getTableRecordKey($record): string
    {
        return $record->date;
    }
}