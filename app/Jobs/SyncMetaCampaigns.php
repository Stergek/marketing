<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\DailyMetric;
use App\Models\Setting;
use App\Services\MetaAdsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncMetaCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $forceSync;
    protected $syncMode;

    public function __construct($date, $forceSync = false, $syncMode = 'both')
    {
        $this->date = $date;
        $this->forceSync = $forceSync;
        $this->syncMode = $syncMode;
    }

    public function handle()
    {
        Log::info("Starting SyncMetaCampaigns job for date {$this->date}, ad_account syncMode: {$this->syncMode}, forceSync: " . ($this->forceSync ? 'true' : 'false'));

        $setting = Setting::first();
        $adAccountId = $setting ? $setting->ad_account_id : null;

        if (empty($adAccountId)) {
            Log::error("SyncMetaCampaigns: Ad Account ID is not set in settings for date {$this->date}.");
            return;
        }

        if (!$this->forceSync && !$this->shouldSync($adAccountId)) {
            Log::info("Skipping sync for date {$this->date} and ad_account_id {$adAccountId} based on sync rules.");
            return;
        }

        $metaAdsService = app(MetaAdsService::class);

        $insights = [];
        $campaigns = [];

        // Fetch ad account-level insights if syncMode includes ad_account
        if (in_array($this->syncMode, ['ad_account', 'both'])) {
            try {
                Log::info("Fetching insights for ad_account_id {$adAccountId} on {$this->date}");
                $insights = $metaAdsService->getAccountInsights($adAccountId, $this->date);
                Log::info("Received insights for {$adAccountId} on {$this->date}: " . json_encode($insights));

                // Prepare data for storage
                $data = [
                    'ad_account_id' => $adAccountId,
                    'date' => $this->date,
                    'spend' => $insights['spend'] ?? '0.00',
                    'cpc' => $insights['cpc'] ?? '0.00',
                    'impressions' => isset($insights['impressions']) ? (int)$insights['impressions'] : 0,
                    'clicks' => isset($insights['clicks']) ? (int)$insights['clicks'] : 0,
                    'inline_link_clicks' => isset($insights['inline_link_clicks']) ? (int)$insights['inline_link_clicks'] : 0,
                    'revenue' => isset($insights['revenue']) ? (float)$insights['revenue'] : 0.00,
                    'roas' => $insights['roas'] ?? '0.00',
                    'cpm' => $insights['cpm'] ?? '0.00',
                    'ctr' => $insights['ctr'] ?? '0.00',
                    'last_synced_at' => now(),
                ];

                Log::info("Prepared data for storage for {$adAccountId} on {$this->date}: " . json_encode($data));

                // Log the raw SQL query for debugging
                DB::enableQueryLog();
                try {
                    DailyMetric::updateOrCreate(
                        [
                            'ad_account_id' => $adAccountId,
                            'date' => $this->date,
                        ],
                        $data
                    );
                    Log::info("Successfully saved daily metrics for {$adAccountId} on {$this->date}");
                    Log::info("SQL Queries executed: " . json_encode(DB::getQueryLog()));
                } catch (\Exception $e) {
                    Log::error("Failed to save daily metrics for {$adAccountId} on {$this->date}: " . $e->getMessage());
                    Log::error("SQL Queries executed: " . json_encode(DB::getQueryLog()));
                    return;
                }
                DB::disableQueryLog();
            } catch (\Exception $e) {
                Log::error("Failed to fetch insights for {$adAccountId} on {$this->date}: " . $e->getMessage());
                return;
            }
        }

        // Fetch campaign-level insights if syncMode includes campaigns
        if (in_array($this->syncMode, ['campaigns', 'both'])) {
            try {
                Log::info("Fetching campaigns for ad_account_id {$adAccountId} on {$this->date}");
                $campaigns = $metaAdsService->getCampaigns($adAccountId, $this->date);
                Log::info("Received campaigns for {$adAccountId} on {$this->date}: " . json_encode($campaigns));

                // Save campaign-level insights to campaigns table
                foreach ($campaigns as $campaign) {
                    try {
                        Campaign::updateOrCreate(
                            [
                                'campaign_id' => $campaign['campaign_id'],
                                'date' => $campaign['date'],
                            ],
                            [
                                'ad_account_id' => $adAccountId,
                                'name' => $campaign['name'],
                                'spend' => $campaign['spend'],
                                'cpc' => $campaign['cpc'],
                                'impressions' => (int)$campaign['impressions'],
                                'clicks' => (int)$campaign['clicks'],
                                'revenue' => (float)$campaign['revenue'],
                                'last_synced_at' => now(),
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::error("Failed to save campaign {$campaign['campaign_id']} for {$adAccountId} on {$this->date}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch campaigns for {$adAccountId} on {$this->date}: " . $e->getMessage());
            }
        }

        // Log the sync result based on the syncMode
        if ($this->syncMode === 'ad_account') {
            Log::info("Completed sync for ad account insights for date {$this->date} and ad_account_id {$adAccountId}");
        } elseif ($this->syncMode === 'campaigns') {
            Log::info("Completed sync for campaign data for date {$this->date} and ad_account_id {$adAccountId}", [
                'campaign_count' => count($campaigns),
            ]);
        } else {
            Log::info("Completed sync for ad account and campaign data for date {$this->date} and ad_account_id {$adAccountId}", [
                'campaign_count' => count($campaigns),
            ]);
        }
    }

    protected function shouldSync($adAccountId)
    {
        Log::info("Checking if sync is needed for ad_account_id {$adAccountId} on date {$this->date}");
        $existingMetric = DailyMetric::where('ad_account_id', $adAccountId)
            ->where('date', $this->date)
            ->first();

        $existingCampaign = Campaign::where('ad_account_id', $adAccountId)
            ->where('date', $this->date)
            ->first();

        // If syncing ad_account or both, check daily_metrics
        $needsAdAccountSync = in_array($this->syncMode, ['ad_account', 'both']) && !$existingMetric;
        if (!$needsAdAccountSync && in_array($this->syncMode, ['ad_account', 'both']) && $existingMetric) {
            $isToday = Carbon::parse($this->date)->isSameDay(now());
            if ($isToday) {
                $lastSyncTime = Carbon::parse($existingMetric->last_synced_at);
                $hoursSinceLastSync = $lastSyncTime->diffInHours(now());
                $needsAdAccountSync = $hoursSinceLastSync >= 1;
            } else {
                $endOfSelectedDate = Carbon::parse($this->date)->endOfDay();
                $lastSyncTime = Carbon::parse($existingMetric->last_synced_at);
                $needsAdAccountSync = $lastSyncTime->lessThan($endOfSelectedDate);
            }
        }

        // If syncing campaigns or both, check campaigns
        $needsCampaignSync = in_array($this->syncMode, ['campaigns', 'both']) && !$existingCampaign;
        if (!$needsCampaignSync && in_array($this->syncMode, ['campaigns', 'both']) && $existingCampaign) {
            $campaignCount = Campaign::where('ad_account_id', $adAccountId)
                ->where('date', $this->date)
                ->count();
            $needsCampaignSync = $campaignCount === 0;
        }

        Log::info("Sync decision for ad_account_id {$adAccountId} on {$this->date}: needsAdAccountSync=" . ($needsAdAccountSync ? 'true' : 'false') . ", needsCampaignSync=" . ($needsCampaignSync ? 'true' : 'false'));
        return $needsAdAccountSync || $needsCampaignSync;
    }
}