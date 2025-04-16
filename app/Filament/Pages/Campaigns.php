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
use Filament\Notifications\Notification; // Add this import

class Campaigns extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.campaigns';

    public $selectedDate;

    public function mount()
    {
        $maxDate = Campaign::max('date');
        $this->selectedDate = $maxDate && \Carbon\Carbon::parse($maxDate)->lte(now())
            ? $maxDate
            : now()->toDateString();
    }

    protected function getModalData($record)
    {
        $data = [];

        // Campaign-level data
        $data[] = [
            'level' => 'Campaign',
            'name' => $record->name,
            'ad_image' => null,
            'spend' => $record->spend,
            'cpc' => $record->cpc,
            'roas' => $record->spend > 0 ? number_format($record->revenue / $record->spend, 2) : 0,
            'cpm' => $record->impressions > 0 ? number_format(($record->spend / $record->impressions) * 1000, 2) : 0,
            'ctr' => $record->impressions > 0 ? number_format(($record->clicks / $record->impressions) * 100, 2) : 0,
            'spend_7d' => $this->getSpend($record->campaign_id, null, null, 7),
            'spend_14d' => $this->getSpend($record->campaign_id, null, null, 14),
            'spend_30d' => $this->getSpend($record->campaign_id, null, null, 30),
            'cpc_7d' => $this->getCPC($record->campaign_id, null, null, 7),
            'cpc_14d' => $this->getCPC($record->campaign_id, null, null, 14),
            'cpc_30d' => $this->getCPC($record->campaign_id, null, null, 30),
            'roas_7d' => $this->getROAS($record->campaign_id, null, null, 7),
            'roas_14d' => $this->getROAS($record->campaign_id, null, null, 14),
            'roas_30d' => $this->getROAS($record->campaign_id, null, null, 30),
            'cpm_7d' => $this->getCPM($record->campaign_id, null, null, 7),
            'cpm_14d' => $this->getCPM($record->campaign_id, null, null, 14),
            'cpm_30d' => $this->getCPM($record->campaign_id, null, null, 30),
            'ctr_7d' => $this->getCTR($record->campaign_id, null, null, 7),
            'ctr_14d' => $this->getCTR($record->campaign_id, null, null, 14),
            'ctr_30d' => $this->getCTR($record->campaign_id, null, null, 30),
        ];

        // Ad set-level data
        $adSets = $record->adSets()->take(2)->get();
        foreach ($adSets as $adSet) {
            $data[] = [
                'level' => 'Ad Set',
                'name' => $adSet->name,
                'ad_image' => null,
                'spend' => $adSet->spend,
                'cpc' => $adSet->cpc,
                'roas' => $adSet->spend > 0 ? number_format($adSet->revenue / $adSet->spend, 2) : 0,
                'cpm' => $adSet->impressions > 0 ? number_format(($adSet->spend / $adSet->impressions) * 1000, 2) : 0,
                'ctr' => $adSet->impressions > 0 ? number_format(($adSet->clicks / $adSet->impressions) * 100, 2) : 0,
                'spend_7d' => $this->getSpend(null, $adSet->ad_set_id, null, 7),
                'spend_14d' => $this->getSpend(null, $adSet->ad_set_id, null, 14),
                'spend_30d' => $this->getSpend(null, $adSet->ad_set_id, null, 30),
                'cpc_7d' => $this->getCPC(null, $adSet->ad_set_id, null, 7),
                'cpc_14d' => $this->getCPC(null, $adSet->ad_set_id, null, 14),
                'cpc_30d' => $this->getCPC(null, $adSet->ad_set_id, null, 30),
                'roas_7d' => $this->getROAS(null, $adSet->ad_set_id, null, 7),
                'roas_14d' => $this->getROAS(null, $adSet->ad_set_id, null, 14),
                'roas_30d' => $this->getROAS(null, $adSet->ad_set_id, null, 30),
                'cpm_7d' => $this->getCPM(null, $adSet->ad_set_id, null, 7),
                'cpm_14d' => $this->getCPM(null, $adSet->ad_set_id, null, 14),
                'cpm_30d' => $this->getCPM(null, $adSet->ad_set_id, null, 30),
                'ctr_7d' => $this->getCTR(null, $adSet->ad_set_id, null, 7),
                'ctr_14d' => $this->getCTR(null, $adSet->ad_set_id, null, 14),
                'ctr_30d' => $this->getCTR(null, $adSet->ad_set_id, null, 30),
            ];

            // Ad-level data
            $ads = $adSet->ads()->take(2)->get();
            foreach ($ads as $ad) {
                $data[] = [
                    'level' => 'Ad',
                    'name' => $ad->name,
                    'ad_image' => $ad->ad_image,
                    'spend' => $ad->spend,
                    'cpc' => $ad->cpc,
                    'roas' => $ad->spend > 0 ? number_format($ad->revenue / $ad->spend, 2) : 0,
                    'cpm' => $ad->impressions > 0 ? number_format(($ad->spend / $ad->impressions) * 1000, 2) : 0,
                    'ctr' => $ad->impressions > 0 ? number_format(($ad->clicks / $ad->impressions) * 100, 2) : 0,
                    'spend_7d' => $this->getSpend(null, null, $ad->ad_id, 7),
                    'spend_14d' => $this->getSpend(null, null, $ad->ad_id, 14),
                    'spend_30d' => $this->getSpend(null, null, $ad->ad_id, 30),
                    'cpc_7d' => $this->getCPC(null, null, $ad->ad_id, 7),
                    'cpc_14d' => $this->getCPC(null, null, $ad->ad_id, 14),
                    'cpc_30d' => $this->getCPC(null, null, $ad->ad_id, 30),
                    'roas_7d' => $this->getROAS(null, null, $ad->ad_id, 7),
                    'roas_14d' => $this->getROAS(null, null, $ad->ad_id, 14),
                    'roas_30d' => $this->getROAS(null, null, $ad->ad_id, 30),
                    'cpm_7d' => $this->getCPM(null, null, $ad->ad_id, 7),
                    'cpm_14d' => $this->getCPM(null, null, $ad->ad_id, 14),
                    'cpm_30d' => $this->getCPM(null, null, $ad->ad_id, 30),
                    'ctr_7d' => $this->getCTR(null, null, $ad->ad_id, 7),
                    'ctr_14d' => $this->getCTR(null, null, $ad->ad_id, 14),
                    'ctr_30d' => $this->getCTR(null, null, $ad->ad_id, 30),
                ];
            }
        }

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Campaign::query()->when($this->selectedDate, fn ($query) => $query->where('date', $this->selectedDate)))
            ->columns([
                TextColumn::make('name')->label('Campaign Name'),
                TextColumn::make('spend')->label('Amount Spent')->money('usd'),
                TextColumn::make('cpc')->label('Cost Per Click')->money('usd'),
                TextColumn::make('roas')->label('ROAS')
                    ->getStateUsing(fn ($record) => $record->spend > 0 ? number_format($record->revenue / $record->spend, 2) : 0),
                TextColumn::make('cpm')->label('CPM')
                    ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->spend / $record->impressions) * 1000, 2) : 0)
                    ->money('usd'),
                TextColumn::make('ctr')->label('CTR (%)')
                    ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->clicks / $record->impressions) * 100, 2) : 0),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Select')
                            ->maxDate(now())
                            // ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $this->selectedDate = $state;
                                if ($state) {
                                    $lockKey = "sync:campaigns:{$state}";
                                    if (!Cache::has($lockKey) && !Campaign::where('date', $state)->exists()) {
                                        Cache::put($lockKey, true, now()->addMinutes(10));
                                        SyncMetaCampaigns::dispatch($state, auth()->id())->afterCommit();
                                        Notification::make()
                                            ->title('Syncing data for ' . $state . '... Please wait a moment and refresh.')
                                            ->success()
                                            ->send();
                                    }
                                    $set('tableFilters.date.date', $state);
                                }
                            }),
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->modalHeading(fn ($record) => 'Details for ' . $record->name)
                    ->modalContent(fn ($record) => view('filament.campaign-details', ['data' => $this->getModalData($record)])),
            ])
            ->emptyStateHeading('No campaigns found')
            ->emptyStateDescription($this->selectedDate ? 'No data for the selected date.' : 'Please select a date to view campaigns.');
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
    }
}
