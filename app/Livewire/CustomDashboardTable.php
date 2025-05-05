<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\DailyMetric;
use App\Models\Setting;
use App\Jobs\SyncMetaCampaigns;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Contracts\TranslatableContentDriver;

class CustomDashboardTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

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

        $this->startDate = $this->startDate ?? now()->subDays(7)->toDateString();
        $this->endDate = $this->endDate ?? now()->toDateString();
        $this->tableFilters['date_range'] = [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'range' => 'last_7_days',
        ];
    }

    public function table(Table $table): Table
    {
        if (empty($this->adAccountId)) {
            return $table
                ->query(DailyMetric::query())
                ->columns([])
                ->filters([])
                ->emptyStateHeading('Configuration Required')
                ->emptyStateDescription('Please configure the Ad Account ID in the Settings page.');
        }

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
            ->filters([])
            ->defaultSort('date', 'desc')
            ->paginated(false)
            ->emptyStateHeading('No metrics found')
            ->emptyStateDescription('No data for the selected date range. A sync job may have been dispatched; please refresh the page in a moment.');
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null; // No translatable content driver needed
    }

    public function render()
    {
        return view('livewire.custom-dashboard-table', [
            'table' => $this->getTable(),
        ]);
    }
}