<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsService
{
    protected $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.meta.access_token');

        if (empty($this->accessToken)) {
            Log::error("MetaAdsService: accessToken is not set in configuration.");
            throw new \Exception("Meta Ads accessToken is not configured.");
        }

        // Log to confirm the version of MetaAdsService being used
        Log::info("Using MetaAdsService version: Simplified for campaign insights only");
    }

    public function getCampaigns($adAccountId, $date)
    {
        // Fetch campaigns with basic fields
        $response = Http::get("https://graph.facebook.com/v22.0/{$adAccountId}/campaigns", [
            'access_token' => $this->accessToken,
            'fields' => 'id,name',
            'limit' => 10,
        ]);

        $responseData = $response->json();
        Log::info("Campaigns API Response for {$adAccountId} on {$date}: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Campaigns API Error: " . json_encode($responseData['error']));
            throw new \Exception(json_encode($responseData['error']));
        }

        $campaigns = $responseData['data'] ?? [];
        Log::info("Fetched " . count($campaigns) . " campaigns for date {$date}");

        // Prepare batch request for campaign insights
        $batch = [];
        foreach ($campaigns as $campaign) {
            $batch[] = [
                'method' => 'GET',
                'relative_url' => "v22.0/{$campaign['id']}/insights?fields=date_start,date_stop,spend,cpc,impressions,clicks,action_values,actions&time_range=" . urlencode(json_encode(['since' => $date, 'until' => $date])) . "&time_increment=1",
            ];
        }

        $result = [];

        if (!empty($batch)) {
            $insightsResponses = $this->batchRequest($batch);
            $insightsData = [];
            foreach ($insightsResponses as $index => $response) {
                $data = json_decode($response['body'], true);
                $insightsData[$campaigns[$index]['id']] = $data['data'][0] ?? null;
            }

            foreach ($campaigns as $campaign) {
                $insights = $insightsData[$campaign['id']] ?? null;
                $spend = 0;
                $cpc = 0;
                $impressions = 0;
                $clicks = 0;
                $revenue = 0;

                if ($insights && $insights['date_start'] === $date) {
                    Log::info("Insights data for campaign {$campaign['id']} on date {$date}: " . json_encode($insights));
                    $spend = $insights['spend'] ?? 0;
                    $cpc = $insights['cpc'] ?? 0;
                    $impressions = $insights['impressions'] ?? 0;
                    $clicks = $insights['clicks'] ?? 0;

                    if (isset($insights['action_values'])) {
                        foreach ($insights['action_values'] as $action) {
                            if (in_array($action['action_type'], ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                                $revenue = $action['value'];
                                break;
                            }
                        }
                    }
                } else {
                    Log::info("No insights data for campaign {$campaign['id']} on date {$date}, using default values.");
                }

                $result[] = [
                    'campaign_id' => $campaign['id'],
                    'name' => $campaign['name'] ?? 'Campaign ' . (count($result) + 1),
                    'date' => $date,
                    'spend' => $spend,
                    'cpc' => $cpc,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'revenue' => $revenue,
                ];
            }
        }

        if (empty($result)) {
            $result[] = [
                'campaign_id' => 'default_' . $adAccountId,
                'name' => 'Campaign 1',
                'date' => $date,
                'spend' => 0,
                'cpc' => 0,
                'impressions' => 0,
                'clicks' => 0,
                'revenue' => 0,
            ];
        }

        return $result;
    }

    protected function batchRequest($batch)
    {
        $response = Http::post("https://graph.facebook.com/v22.0/", [
            'access_token' => $this->accessToken,
            'batch' => json_encode($batch),
        ]);

        $responseData = $response->json();
        Log::info("Batch API Response: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Batch API Error: " . json_encode($responseData['error']));
            throw new \Exception(json_encode($responseData['error']));
        }

        // Log x-app-usage header if available
        if ($response->hasHeader('x-app-usage')) {
            Log::info("API Usage Headers", ['x-app-usage' => $response->header('x-app-usage')]);
        }

        return $responseData;
    }
}