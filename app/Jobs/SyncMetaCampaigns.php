<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MetaAdsService;
use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class SyncMetaCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $date;
    protected $adAccountId;
    protected $metaAdsService;

    public $tries = 3;
    public $backoff = [1800, 3600, 7200]; // 30min, 1hr, 2hr for rate limits

    public function __construct($date, $adAccountId)
    {
        $this->date = $date;
        $this->adAccountId = $adAccountId;
        $this->metaAdsService = resolve(MetaAdsService::class);
    }

    public function handle()
    {
        try {
            // Fetch campaigns with insights using the service
            $campaigns = $this->metaAdsService->getCampaigns($this->adAccountId, $this->date);

            Log::info("Fetched " . count($campaigns) . " campaigns for date {$this->date} and ad account {$this->adAccountId}");

            foreach ($campaigns as $campaign) {
                Log::info("Processing campaign", ['campaign_id' => $campaign['campaign_id']]);

                // Save campaign with fetched insights
                $campaignModel = Campaign::updateOrCreate(
                    [
                        'campaign_id' => $campaign['campaign_id'],
                        'date' => $this->date,
                        'ad_account_id' => $this->adAccountId,
                    ],
                    [
                        'name' => $campaign['name'],
                        'spend' => $campaign['spend'] ?? 0,
                        'clicks' => $campaign['clicks'] ?? 0,
                        'impressions' => $campaign['impressions'] ?? 0,
                        'cpc' => $campaign['cpc'] ?? 0,
                        'revenue' => $campaign['revenue'] ?? 0,
                    ]
                );

                Log::info("Saved campaign record", [
                    'campaign_id' => $campaign['campaign_id'],
                    'date' => $this->date,
                    'metrics' => [
                        'spend' => $campaignModel->spend,
                        'clicks' => $campaignModel->clicks,
                        'impressions' => $campaignModel->impressions,
                        'cpc' => $campaignModel->cpc,
                        'revenue' => $campaignModel->revenue,
                    ],
                ]);
            }

            Log::info("Completed sync for date: {$this->date}, fetched " . count($campaigns) . " campaigns");

        } catch (\Exception $e) {
            if ($this->isRateLimitError($e)) {
                Log::warning("Rate limit hit for ad account {$this->adAccountId} on date {$this->date}. Releasing job for retry after 1 hour.");
                $this->release(3600); // Retry after 1 hour
                return;
            }

            Log::error("Error syncing Meta campaigns for ad account {$this->adAccountId}.", [
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    protected function isRateLimitError(\Exception $e)
    {
        $error = json_decode($e->getMessage(), true);
        return isset($error['error']['code']) && $error['error']['code'] == 17 && $error['error']['error_subcode'] == 2446079;
    }
}