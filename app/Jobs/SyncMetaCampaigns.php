<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MetaAdsService;
use App\Models\Campaign;
use App\Models\AdSet;
use App\Models\Ad;
use Illuminate\Support\Facades\Log;

class SyncMetaCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $userId;

    public function __construct($date, $userId)
    {
        $this->date = $date;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            Log::info("Starting sync for date: {$this->date}, user: {$this->userId}");

            $metaAdsService = new MetaAdsService();
            $campaigns = $metaAdsService->getCampaigns($this->date);
            Log::info("Processing " . count($campaigns) . " campaigns for date {$this->date}");

            foreach ($campaigns as $campaignData) {
                $campaign = Campaign::updateOrCreate(
                    [
                        'campaign_id' => $campaignData['campaign_id'],
                        'date' => $this->date,
                    ],
                    [
                        'name' => $campaignData['name'],
                        'spend' => $campaignData['spend'],
                        'cpc' => $campaignData['cpc'],
                        'revenue' => $campaignData['revenue'],
                        'impressions' => $campaignData['impressions'],
                        'clicks' => $campaignData['clicks'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                Log::info("Processing campaign", ['campaign_id' => $campaignData['campaign_id']]);

                $adSets = $metaAdsService->getAdSets($campaignData['campaign_id'], $this->date);
                foreach ($adSets as $adSetData) {
                    $adSet = AdSet::updateOrCreate(
                        [
                            'ad_set_id' => $adSetData['ad_set_id'],
                            'date' => $this->date,
                        ],
                        [
                            'name' => $adSetData['name'],
                            'spend' => $adSetData['spend'],
                            'cpc' => $adSetData['cpc'],
                            'revenue' => $adSetData['revenue'],
                            'impressions' => $adSetData['impressions'],
                            'clicks' => $adSetData['clicks'],
                            'campaign_id' => $campaign->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $ads = $metaAdsService->getAds($adSetData['ad_set_id'], $this->date);
                    foreach ($ads as $adData) {
                        Ad::updateOrCreate(
                            [
                                'ad_id' => $adData['ad_id'],
                                'date' => $this->date,
                            ],
                            [
                                'name' => $adData['name'],
                                'ad_image' => $adData['ad_image'],
                                'spend' => $adData['spend'],
                                'cpc' => $adData['cpc'],
                                'revenue' => $adData['revenue'],
                                'impressions' => $adData['impressions'],
                                'clicks' => $adData['clicks'],
                                'ad_set_id' => $adSet->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }

            Log::info("Completed sync for date: {$this->date}, fetched " . count($campaigns) . " campaigns");
        } catch (\Exception $e) {
            Log::error("Error syncing campaigns for date {$this->date}", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}
