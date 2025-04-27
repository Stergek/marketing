<?php

namespace App\Filament\Pages;

use App\Jobs\SyncCampaigns;
use App\Models\Campaign;
use App\Models\Setting;
use Carbon\Carbon;
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

class Campaigns extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static string $view = 'filament.pages.campaigns';
    protected static ?string $navigationLabel = 'Campaigns';
    protected static ?string $title = 'Campaigns';

    public $date;
    public $adAccountId;

    protected $queryString = [
        'date' => ['except' => ''],
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

        // Debug: Log the current value of now()
        Log::info("Debug: Current date from now()", ['now' => now()->toDateTimeString()]);

        // Check if date is stored in the session or query string
        $this->date = $this->date ?? Session::get('campaigns_date') ?? now()->toDateString();

        // Debug: Log session and query string date
        Log::info("Debug: Session and query string date for Campaigns", [
            'session_date' => Session::get('campaigns_date'),
            'query_date' => $this->date,
        ]);

        // Initialize the filter state with the selected date
        $this->tableFilters['date']['date'] = $this->date;
        Session::put('campaigns_date', $this->date);

        Log::info("Campaigns page loaded", [
            'ad_account_id' => $this->adAccountId,
            'date' => $this->date,
            'table_filters' => $this->tableFilters,
        ]);
    }

    public function table(FilamentTable $table): FilamentTable
    {
        if (empty($this->adAccountId)) {
            return $table
                ->query(Campaign::query())
                ->columns([])
                ->filters([])
                ->emptyStateHeading('Configuration Required')
                ->emptyStateDescription('Please configure the Ad Account ID in the Settings page.');
        }

        // Validate date
        try {
            $selectedDate = Carbon::parse($this->date);
        } catch (\Exception $e) {
            Log::error("Campaigns Page: Invalid date format provided", [
                'date' => $this->date,
            ]);
            Notification::make()
                ->title('Error')
                ->body('Invalid date format. Please use YYYY-MM-DD.')
                ->danger()
                ->send();
            return $table->query(Campaign::query())->columns([])->filters([]);
        }

        // Check for missing campaign data and dispatch sync job
        $campaign = Campaign::where('ad_account_id', $this->adAccountId)
            ->where('date', $this->date)
            ->first();

        if (!$campaign) {
            Log::info("Dispatching campaign sync job for date {$this->date} on Campaigns page.");
            SyncCampaigns::dispatch($this->date, false);
        } else {
            Log::info("Campaign data exists for date {$this->date} and ad_account_id {$this->adAccountId}");
        }

        // Debug: Log the query being executed
        $query = Campaign::query()
            ->where('ad_account_id', $this->adAccountId)
            ->where('date', $this->date);
        Log::info("Executing table query", [
            'date' => $this->date,
            'ad_account_id' => $this->adAccountId,
            'query' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('campaign_id')
                    ->label('Campaign ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Campaign Name')
                    ->sortable(),
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
                    ->getStateUsing(fn ($record) => $record->spend > 0 ? number_format($record->revenue / $record->spend, 2) : '0.00')
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    }),
                TextColumn::make('cpm')
                    ->label('CPM')
                    ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->spend / $record->impressions) * 1000, 2) : '0.00')
                    ->money('usd')
                    ->color(function ($state) {
                        if ($state <= 10) return 'success';
                        if ($state <= 20) return 'warning';
                        return 'danger';
                    }),
                TextColumn::make('ctr')
                    ->label('CTR (%)')
                    ->getStateUsing(fn ($record) => $record->inline_link_click_ctr ?? '0.00')
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    }),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Date')
                            ->maxDate(now())
                            ->default($this->date)
                            ->reactive()
                            ->displayFormat('d-m-Y')
                            ->format('Y-m-d')
                            ->afterStateUpdated(function ($state, callable $set) {
                                Log::info("Date filter updated", [
                                    'new_date' => $state,
                                    'old_date' => $this->date,
                                    'table_filters_before' => $this->tableFilters,
                                ]);
                                $this->date = $state ?? $this->date;
                                $set('date', $this->date);
                                $this->tableFilters['date']['date'] = $this->date;
                                Session::put('campaigns_date', $this->date);
                                Log::info("Resetting table for date", ['date' => $this->date]);
                                $this->resetTable();
                                Log::info("Table filters updated", [
                                    'new_date' => $this->date,
                                    'table_filters_after' => $this->tableFilters,
                                ]);
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['date'])) {
                            Log::info("Applying date filter to query", [
                                'date' => $data['date'],
                            ]);
                            $query->where('date', $data['date']);
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['date'])) {
                            return 'Date: ' . Carbon::parse($data['date'])->format('d-m-Y');
                        }
                        return null;
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->actions([])
            ->emptyStateHeading('No campaigns found')
            ->emptyStateDescription('No campaign data for the selected date. A sync job may have been dispatched; please refresh the page in a moment.');
    }

    /**
     * Override the table record key to use a combination of campaign_id and date.
     */
    public function getTableRecordKey($record): string
    {
        return $record->campaign_id . '-' . $record->date;
    }
}