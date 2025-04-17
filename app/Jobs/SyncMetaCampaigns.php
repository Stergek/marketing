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
use App\Models\AdSet;
use App\Models\Ad;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SyncMetaCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $date;
    protected $userId;
    protected $metaAdsService;

    public $tries = 3;
    public $backoff = [60, 120, 180];

    public function __construct($date, $userId)
    {
        $this->date = $date;
        $this->userId = $userId;
        $this->metaAdsService = new MetaAdsService();
    }

    public function handle()
    {
        // Fetch ad accounts
        $adAccounts = ['act_1124601191667195'];

        foreach ($adAccounts as $adAccountId) {
            try {
                // Fetch campaigns using the service
                $campaigns = $this->metaAdsService->getCampaigns($this->date);

                Log::info("Fetched " . count($campaigns) . " campaigns for date {$this->date}");

                foreach ($campaigns as $campaign) {
                    Log::info("Processing campaign", ['campaign_id' => $campaign['campaign_id']]);

                    // Save campaign with placeholder data (metrics will be updated later)
                    $campaignModel = Campaign::updateOrCreate(
                        [
                            'campaign_id' => $campaign['campaign_id'],
                            'date' => $this->date,
                        ],
                        [
                            'name' => $campaign['name'],
                            'spend' => 0,
                            'clicks' => 0,
                            'impressions' => 0,
                            'cpc' => 0,
                            'revenue' => 0,
                        ]
                    );

                    Log::info("Saved placeholder campaign record", [
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

                    // Fetch ad sets with insights
                    $adSets = $this->metaAdsService->getAdSets($campaign['campaign_id'], $this->date);

                    Log::info("Fetched " . count($adSets) . " ad sets for campaign {$campaign['campaign_id']}");

                    foreach ($adSets as $adSet) {
                        // Save ad set data using the campaigns table's id as the foreign key
                        $adSetModel = AdSet::updateOrCreate(
                            [
                                'ad_set_id' => $adSet['ad_set_id'],
                                'date' => $this->date,
                            ],
                            [
                                'campaign_id' => $campaignModel->id, // Use the campaigns table's id
                                'name' => $adSet['name'],
                                'spend' => $adSet['spend'],
                                'clicks' => $adSet['clicks'],
                                'impressions' => $adSet['impressions'],
                                'cpc' => $adSet['cpc'],
                                'revenue' => $adSet['revenue'],
                            ]
                        );

                        Log::info("Saved ad set record", [
                            'ad_set_id' => $adSet['ad_set_id'],
                            'date' => $this->date,
                            'metrics' => [
                                'spend' => $adSet['spend'],
                                'clicks' => $adSet['clicks'],
                                'impressions' => $adSet['impressions'],
                                'cpc' => $adSet['cpc'],
                                'revenue' => $adSet['revenue'],
                            ],
                        ]);

                        // Fetch ads with insights
                        $ads = $this->metaAdsService->getAds($adSet['ad_set_id'], $this->date);

                        foreach ($ads as $ad) {
                            // Save ad data using the ad_sets table's id as the foreign key
                            Ad::updateOrCreate(
                                [
                                    'ad_id' => $ad['ad_id'],
                                    'date' => $this->date,
                                ],
                                [
                                    'ad_set_id' => $adSetModel->id, // Use the ad_sets table's id
                                    'name' => $ad['name'],
                                    'ad_image' => $ad['ad_image'],
                                    'spend' => $ad['spend'],
                                    'clicks' => $ad['clicks'],
                                    'impressions' => $ad['impressions'],
                                    'cpc' => $ad['cpc'],
                                    'revenue' => $ad['revenue'],
                                ]
                            );
                        }
                    }

                    // Log the campaign record before aggregation
                    $campaignBeforeAggregation = Campaign::where('campaign_id', $campaign['campaign_id'])
                        ->where('date', $this->date)
                        ->first();

                    Log::info("Campaign record before aggregation", [
                        'campaign_id' => $campaign['campaign_id'],
                        'date' => $this->date,
                        'metrics' => [
                            'spend' => $campaignBeforeAggregation->spend,
                            'clicks' => $campaignBeforeAggregation->clicks,
                            'impressions' => $campaignBeforeAggregation->impressions,
                            'cpc' => $campaignBeforeAggregation->cpc,
                            'revenue' => $campaignBeforeAggregation->revenue,
                        ],
                    ]);

                    // Aggregate ad set metrics to update campaign metrics
                    $adSetMetrics = AdSet::where('campaign_id', $campaignModel->id) // Use the campaigns table's id
                        ->where('date', $this->date)
                        ->selectRaw('
                            COALESCE(SUM(spend), 0) as total_spend,
                            COALESCE(SUM(clicks), 0) as total_clicks,
                            COALESCE(SUM(impressions), 0) as total_impressions,
                            COALESCE(SUM(revenue), 0) as total_revenue,
                            CASE
                                WHEN SUM(clicks) > 0 THEN SUM(spend) / SUM(clicks)
                                ELSE 0
                            END as avg_cpc
                        ')
                        ->first();

                    Log::info("Aggregated ad set metrics for campaign {$campaign['campaign_id']} on date {$this->date}", [
                        'total_spend' => $adSetMetrics->total_spend,
                        'total_clicks' => $adSetMetrics->total_clicks,
                        'total_impressions' => $adSetMetrics->total_impressions,
                        'avg_cpc' => $adSetMetrics->avg_cpc,
                        'total_revenue' => $adSetMetrics->total_revenue,
                    ]);

                    // Update campaign with aggregated metrics
                    $campaignModel->update([
                        'spend' => $adSetMetrics->total_spend,
                        'clicks' => $adSetMetrics->total_clicks,
                        'impressions' => $adSetMetrics->total_impressions,
                        'cpc' => $adSetMetrics->avg_cpc,
                        'revenue' => $adSetMetrics->total_revenue,
                    ]);

                    // Log the campaign record after aggregation
                    $campaignAfterAggregation = Campaign::where('campaign_id', $campaign['campaign_id'])
                        ->where('date', $this->date)
                        ->first();

                    Log::info("Campaign record after aggregation", [
                        'campaign_id' => $campaign['campaign_id'],
                        'date' => $this->date,
                        'metrics' => [
                            'spend' => $campaignAfterAggregation->spend,
                            'clicks' => $campaignAfterAggregation->clicks,
                            'impressions' => $campaignAfterAggregation->impressions,
                            'cpc' => $campaignAfterAggregation->cpc,
                            'revenue' => $campaignAfterAggregation->revenue,
                        ],
                    ]);
                }

                Log::info("Completed sync for date: {$this->date}, fetched " . count($campaigns) . " campaigns");

                // Remove the cache lock after the sync completes
                $lockKey = "sync:campaigns:{$this->date}";
                Cache::forget($lockKey);

            } catch (\Exception $e) {
                Log::error("Error syncing Meta campaigns for ad account {$adAccountId}.", [
                    'error' => $e->getMessage(),
                ]);

                // Fetch the user model using the userId
                $user = User::find($this->userId);

                if ($user) {
                    Notification::make()
                        ->title('Error Syncing Data')
                        ->body("An error occurred while syncing data for {$this->date}. Please try again later.")
                        ->danger()
                        ->sendToDatabase($user);
                } else {
                    Log::error("User with ID {$this->userId} not found for sending notification.");
                }

                $this->fail($e);
            }
        }
    }
}
