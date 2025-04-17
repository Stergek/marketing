<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\Campaign;
use App\Models\AdSet;
use App\Models\Ad;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Jobs\SyncMetaCampaigns;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class Campaigns extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.campaigns';

    public $selectedDate;
    public $shouldPoll = false;
    public $syncing = false;

    public function mount()
    {
        $maxDate = Campaign::max('date');
        $this->selectedDate = $maxDate && \Carbon\Carbon::parse($maxDate)->lte(now())
            ? $maxDate
            : now()->toDateString();
    }

    protected function getModalData($record)
    {
        $record->load([
            'adSets' => fn ($query) => $query->take(2),
            'adSets.ads' => fn ($query) => $query->take(2),
        ]);

        $data = [];

        $data[] = $this->buildLevelData('Campaign', $record, $record->campaign_id, null, null);

        foreach ($record->adSets as $adSet) {
            $data[] = $this->buildLevelData('Ad Set', $adSet, null, $adSet->ad_set_id, null);

            foreach ($adSet->ads as $ad) {
                $data[] = $this->buildLevelData('Ad', $ad, null, null, $ad->ad_id, $ad->ad_image);
            }
        }

        return $data;
    }

    protected function buildLevelData($level, $record, $campaignId = null, $adSetId = null, $adId = null, $adImage = null)
    {
        $roas = $record->spend > 0 ? number_format($record->revenue / $record->spend, 2) : 0;
        $cpm = $record->impressions > 0 ? number_format(($record->spend / $record->impressions) * 1000, 2) : 0;
        $ctr = $record->impressions > 0 ? number_format(($record->clicks / $record->impressions) * 100, 2) : 0;

        $periods = [7, 14, 30];
        $metrics = ['spend', 'cpc', 'roas', 'cpm', 'ctr'];
        $historicalData = [];

        foreach ($periods as $days) {
            foreach ($metrics as $metric) {
                $method = 'get' . ucfirst($metric);
                $historicalData["{$metric}_{$days}d"] = $this->$method($campaignId, $adSetId, $adId, $days);
            }
        }

        return array_merge([
            'level' => $level,
            'name' => $record->name,
            'ad_image' => $adImage,
            'spend' => $record->spend,
            'cpc' => $record->cpc,
            'roas' => $roas,
            'cpm' => $cpm,
            'ctr' => $ctr,
        ], $historicalData);
    }

    protected function getPreviousDayValue($campaignId, $metric)
    {
        $previousDate = \Carbon\Carbon::parse($this->selectedDate)->subDay()->toDateString();
        $previousRecord = Campaign::where('campaign_id', $campaignId)
            ->where('date', $previousDate)
            ->first();

        return $previousRecord ? $previousRecord->$metric : null;
    }

    public function table(Table $table): Table
{
    if ($this->selectedDate) {
        $campaignCount = Campaign::where('date', $this->selectedDate)->count();
        // Only poll if a sync is in progress (fewer than 5 campaigns and cache lock exists)
        $lockKey = "sync:campaigns:{$this->selectedDate}";
        $this->shouldPoll = $campaignCount < 5 && Cache::has($lockKey);
        $this->syncing = $this->shouldPoll;
    }

    // Pre-fetch data for calculated summaries (ROAS, CPM, CTR)
    $summaryData = $this->selectedDate
        ? Campaign::where('date', $this->selectedDate)
            ->selectRaw('SUM(spend) as total_spend, SUM(revenue) as total_revenue, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks')
            ->first()
        : null;

    $totalSpend = $summaryData ? $summaryData->total_spend : 0;
    $avgCpc = $this->selectedDate ? Campaign::where('date', $this->selectedDate)->avg('cpc') : 0;
    $totalRoas = $summaryData && $summaryData->total_spend > 0 ? $summaryData->total_revenue / $summaryData->total_spend : 0;
    $avgRoas = $totalRoas;
    $totalCpm = $summaryData && $summaryData->total_impressions > 0 ? ($summaryData->total_spend / $summaryData->total_impressions) * 1000 : 0;
    $avgCpm = $totalCpm;
    $totalCtr = $summaryData && $summaryData->total_impressions > 0 ? ($summaryData->total_clicks / $summaryData->total_impressions) * 100 : 0;
    $avgCtr = $totalCtr;

    return $table
        ->query(Campaign::query()->when($this->selectedDate, fn ($query) => $query->where('date', $this->selectedDate)))
        ->columns([
            TextColumn::make('date')
                ->label('Date')
                ->sortable()
                ->toggleable(),
            TextColumn::make('name')
                ->label('Campaign Name')
                ->searchable()
                ->sortable(),
            TextColumn::make('spend')
                ->label('Spent')
                ->money('usd')
                ->sortable()
                ->toggleable()
                ->color(function ($record) {
                    $previousSpend = $this->getPreviousDayValue($record->campaign_id, 'spend');
                    if ($previousSpend !== null && $record->spend > $previousSpend) {
                        return 'danger'; // Red if spend increased
                    }
                    return null;
                })
                ->summarize([
                    \Filament\Tables\Columns\Summarizers\Sum::make()
                        ->label('Total Spend')
                        ->money('usd'),
                ]),
            TextColumn::make('cpc')
                ->label('CPC')
                ->money('usd')
                ->sortable()
                ->toggleable()
                ->color(function ($record) {
                    $previousCpc = $this->getPreviousDayValue($record->campaign_id, 'cpc');
                    if ($previousCpc !== null && $record->cpc > $previousCpc) {
                        return 'danger'; // Red if CPC increased
                    }
                    return null;
                })
                ->summarize([
                    \Filament\Tables\Columns\Summarizers\Average::make()
                        ->label('Average')
                        ->money('usd'),
                ]),
            TextColumn::make('roas')
                ->label('ROAS')
                ->getStateUsing(fn ($record) => $record->spend > 0 ? number_format($record->revenue / $record->spend, 2) : 0)
                ->color(function ($state) {
                    if ($state >= 2) return 'success';
                    if ($state >= 1) return 'warning';
                    return 'danger';
                })
                ->toggleable()
                ->summarize([
                    \Filament\Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Average')
                        ->using(fn () => number_format($avgRoas, 2)),
                ]),
            TextColumn::make('cpm')
                ->label('CPM')
                ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->spend / $record->impressions) * 1000, 2) : 0)
                ->money('usd')
                ->color(function ($state) {
                    if ($state <= 10) return 'success';
                    if ($state <= 20) return 'warning';
                    return 'danger';
                })
                ->toggleable()
                ->summarize([
                    \Filament\Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Average')
                        ->money('usd')
                        ->using(fn () => number_format($avgCpm, 2)),
                ]),
            TextColumn::make('ctr')
                ->label('CTR (%)')
                ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->clicks / $record->impressions) * 100, 2) : 0)
                ->color(function ($state) {
                    if ($state >= 2) return 'success';
                    if ($state >= 1) return 'warning';
                    return 'danger';
                })
                ->toggleable()
                ->summarize([
                    \Filament\Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Average')
                        ->using(fn () => number_format($avgCtr, 2) . '%'),
                ]),
        ])
        ->filters([
            Filter::make('date')
                ->form([
                    DatePicker::make('date')
                        ->label('Select')
                        ->maxDate(now())
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $this->selectedDate = $state;
                            if ($state) {
                                $lockKey = "sync:campaigns:{$state}";
                                if (!Cache::has($lockKey)) {
                                    Cache::put($lockKey, true, now()->addMinutes(10));
                                    SyncMetaCampaigns::dispatch($state, auth()->id())->afterCommit();
                                    Notification::make()
                                        ->title('Syncing data for ' . $state . '... Auto-refreshing in a few seconds.')
                                        ->success()
                                        ->send();
                                    $this->shouldPoll = true;
                                    $this->syncing = true;
                                } else {
                                    // Reset polling if no sync is needed
                                    $this->shouldPoll = false;
                                    $this->syncing = false;
                                }
                                $set('tableFilters.date.date', $state);
                                $this->dispatch('$refresh');
                            }
                        }),
                ]),
            Filter::make('spend')
                ->form([
                    \Filament\Forms\Components\TextInput::make('spend_min')
                        ->label('Min Spend')
                        ->numeric(),
                    \Filament\Forms\Components\TextInput::make('spend_max')
                        ->label('Max Spend')
                        ->numeric(),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['spend_min'], fn ($q) => $q->where('spend', '>=', $data['spend_min']))
                        ->when($data['spend_max'], fn ($q) => $q->where('spend', '<=', $data['spend_max']));
                }),
        ])
        ->actions([
            Action::make('view')
                ->label('View')
                ->modalHeading(fn ($record) => 'Details for ' . $record->name)
                ->modalContent(fn ($record) => view('filament.campaign-details', ['data' => $this->getModalData($record)])),
        ])
        ->headerActions([
            \Filament\Tables\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->dispatch('$refresh');
                }),
        ])
        ->bulkActions([
            \Filament\Tables\Actions\BulkAction::make('delete')
                ->label('Delete Selected')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(function ($records) {
                    $records->each->delete();
                    Notification::make()
                        ->title('Selected campaigns deleted.')
                        ->success()
                        ->send();
                })
                ->deselectRecordsAfterCompletion(),
            \Filament\Tables\Actions\BulkAction::make('sync_multiple_dates')
                ->label('Sync Multiple Dates')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $startDate = \Carbon\Carbon::parse($data['start_date']);
                    $endDate = \Carbon\Carbon::parse($data['end_date']);
                    $userId = auth()->id() ?? 1;

                    // Limit the number of days to sync at once to prevent rate limit issues
                    $maxDays = 5;
                    $dayCount = $startDate->diffInDays($endDate) + 1;

                    if ($dayCount > $maxDays) {
                        Notification::make()
                            ->title('Too Many Days Selected')
                            ->body("Please select a range of {$maxDays} days or fewer to avoid API rate limits.")
                            ->danger()
                            ->send();
                        return;
                    }

                    $dates = [];
                    $delay = 0;
                    $delayIncrement = 300;

                    for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                        $dateString = $date->toDateString();
                        $lockKey = "sync:campaigns:{$dateString}";
                        if (!Cache::has($lockKey)) {
                            Cache::put($lockKey, true, now()->addMinutes(10));
                            SyncMetaCampaigns::dispatch($dateString, $userId)
                                ->afterCommit()
                                ->delay($delay);
                            $dates[] = $dateString;
                            $delay += $delayIncrement;
                        }
                    }

                    Notification::make()
                        ->title('Sync triggered for dates: ' . implode(', ', $dates))
                        ->success()
                        ->send();
                    $this->shouldPoll = true;
                    $this->syncing = true;
                }),
        ])
        ->emptyStateHeading('No campaigns found')
        ->emptyStateDescription($this->selectedDate ? 'No data for the selected date.' : 'Please select a date to view campaigns.')
        ->poll($this->shouldPoll ? '5s' : null);

}

    public function getSpend($campaignId, $adSetId = null, $adId = null, $days)
    {
        $endDate = \Carbon\Carbon::parse($this->selectedDate);
        $startDate = $endDate->copy()->subDays($days);

        if ($adId) {
            return Ad::where('ad_id', $adId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('spend');
        } elseif ($adSetId) {
            return AdSet::where('ad_set_id', $adSetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('spend');
        } else {
            return Campaign::where('campaign_id', $campaignId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('spend');
        }
    }

    public function getCPC($campaignId, $adSetId = null, $adId = null, $days)
    {
        $endDate = \Carbon\Carbon::parse($this->selectedDate);
        $startDate = $endDate->copy()->subDays($days);

        if ($adId) {
            return Ad::where('ad_id', $adId)
                ->whereBetween('date', [$startDate, $endDate])
                ->avg('cpc');
        } elseif ($adSetId) {
            return AdSet::where('ad_set_id', $adSetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->avg('cpc');
        } else {
            return Campaign::where('campaign_id', $campaignId)
                ->whereBetween('date', [$startDate, $endDate])
                ->avg('cpc');
        }
    }

    public function getROAS($campaignId, $adSetId = null, $adId = null, $days)
    {
        $endDate = \Carbon\Carbon::parse($this->selectedDate);
        $startDate = $endDate->copy()->subDays($days);

        if ($adId) {
            $stats = Ad::where('ad_id', $adId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(revenue) as total_revenue, SUM(spend) as total_spend')
                ->first();
        } elseif ($adSetId) {
            $stats = AdSet::where('ad_set_id', $adSetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(revenue) as total_revenue, SUM(spend) as total_spend')
                ->first();
        } else {
            $stats = Campaign::where('campaign_id', $campaignId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(revenue) as total_revenue, SUM(spend) as total_spend')
                ->first();
        }

        return $stats->total_spend > 0 ? $stats->total_revenue / $stats->total_spend : 0;
    }

    public function getCPM($campaignId, $adSetId = null, $adId = null, $days)
    {
        $endDate = \Carbon\Carbon::parse($this->selectedDate);
        $startDate = $endDate->copy()->subDays($days);

        if ($adId) {
            $stats = Ad::where('ad_id', $adId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(spend) as total_spend, SUM(impressions) as total_impressions')
                ->first();
        } elseif ($adSetId) {
            $stats = AdSet::where('ad_set_id', $adSetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(spend) as total_spend, SUM(impressions) as total_impressions')
                ->first();
        } else {
            $stats = Campaign::where('campaign_id', $campaignId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(spend) as total_spend, SUM(impressions) as total_impressions')
                ->first();
        }

        return $stats->total_impressions > 0 ? ($stats->total_spend / $stats->total_impressions) * 1000 : 0;
    }

    public function getCTR($campaignId, $adSetId = null, $adId = null, $days)
    {
        $endDate = \Carbon\Carbon::parse($this->selectedDate);
        $startDate = $endDate->copy()->subDays($days);

        if ($adId) {
            $stats = Ad::where('ad_id', $adId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(clicks) as total_clicks, SUM(impressions) as total_impressions')
                ->first();
        } elseif ($adSetId) {
            $stats = AdSet::where('ad_set_id', $adSetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(clicks) as total_clicks, SUM(impressions) as total_impressions')
                ->first();
        } else {
            $stats = Campaign::where('campaign_id', $campaignId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(clicks) as total_clicks, SUM(impressions) as total_impressions')
                ->first();
        }

        return $stats->total_impressions > 0 ? ($stats->total_clicks / $stats->total_impressions) * 100 : 0;
    }

    public function getStats()
    {
        if (!$this->selectedDate) {
            return [
                'total_spend' => 0,
                'average_cpc' => 0,
                'total_ads' => 0,
                'average_roas' => 0,
                'average_cpm' => 0,
                'average_ctr' => 0,
                'selected_date' => 'No date selected',
            ];
        }

        $cacheKey = "campaign_stats_{$this->selectedDate}";
        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $stats = Campaign::where('date', $this->selectedDate)
                ->selectRaw('SUM(spend) as total_spend, AVG(cpc) as average_cpc, SUM(revenue) as total_revenue, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks')
                ->first();

            $totalAds = Ad::whereHas('adSet.campaign', fn ($query) => $query->where('date', $this->selectedDate))
                ->count();

            $averageRoas = $stats->total_spend > 0 ? $stats->total_revenue / $stats->total_spend : 0;
            $averageCpm = $stats->total_impressions > 0 ? ($stats->total_spend / $stats->total_impressions) * 1000 : 0;
            $averageCtr = $stats->total_impressions > 0 ? ($stats->total_clicks / $stats->total_impressions) * 100 : 0;

            return [
                'total_spend' => round($stats->total_spend ?? 0, 2),
                'average_cpc' => round($stats->average_cpc ?? 0, 2),
                'total_ads' => $totalAds,
                'average_roas' => round($averageRoas, 2),
                'average_cpm' => round($averageCpm, 2),
                'average_ctr' => round($averageCtr, 2),
                'selected_date' => $this->selectedDate,
            ];
        });

        return $stats;
    }
}
