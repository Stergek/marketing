<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Setting;
use App\Services\MetaAdsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $forceSync;

    public function __construct($date, $forceSync = false)
    {
        $this->date = $date;
        $this->forceSync = $forceSync;
    }

    public function handle()
    {
        Log::info("Starting SyncCampaigns job for date {$this->date}, forceSync: " . ($this->forceSync ? 'true' : 'false'));

        $setting = Setting::first();
        $adAccountId = $setting ? $setting->ad_account_id : null;

        if (empty($adAccountId)) {
            Log::error("SyncCampaigns: Ad Account ID is not set in settings for date {$this->date}.");
            return;
        }

        if (!$this->forceSync && !$this->shouldSync($adAccountId)) {
            Log::info("Skipping sync for date {$this->date} and ad_account_id {$adAccountId} based on sync rules.");
            return;
        }

        $metaAdsService = app(MetaAdsService::class);

        try {
            Log::info("Fetching campaigns for ad_account_id {$adAccountId} on {$this->date}");
            $campaigns = $metaAdsService->getCampaigns($adAccountId, $this->date);
            Log::info("Received campaigns for {$adAccountId} on {$this->date}: " . json_encode($campaigns));

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
                            'impressions' => (int)($campaign['impressions'] ?? 0),
                            'clicks' => (int)($campaign['clicks'] ?? 0),
                            'inline_link_clicks' => (int)($campaign['inline_link_clicks'] ?? 0),
                            'inline_link_click_ctr' => number_format((float)($campaign['inline_link_click_ctr'] ?? 0), 2, '.', ''),
                            'revenue' => (float)($campaign['revenue'] ?? 0),
                            'last_synced_at' => now(),
                        ]
                    );
                    Log::info("Successfully saved campaign {$campaign['campaign_id']} for {$adAccountId} on {$this->date}");
                } catch (\Exception $e) {
                    Log::error("Failed to save campaign {$campaign['campaign_id']} for {$adAccountId} on {$this->date}: " . $e->getMessage());
                }
            }

            Log::info("Completed sync for campaign data for date {$this->date} and ad_account_id {$adAccountId}", [
                'campaign_count' => count($campaigns),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to fetch campaigns for {$adAccountId} on {$this->date}: " . $e->getMessage());
        }
    }

    protected function shouldSync($adAccountId)
    {
        Log::info("Checking if sync is needed for ad_account_id {$adAccountId} on date {$this->date}");
        $existingCampaign = Campaign::where('ad_account_id', $adAccountId)
            ->where('date', $this->date)
            ->first();

        $needsSync = !$existingCampaign;
        Log::info("Sync decision for ad_account_id {$adAccountId} on {$this->date}: needsSync=" . ($needsSync ? 'true' : 'false'));
        return $needsSync;
    }
}