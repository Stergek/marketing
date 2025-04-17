<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsService
{
    protected $accessToken;
    protected $adAccountId;

    public function __construct()
    {
        $this->accessToken = config('services.meta.access_token');
        $this->adAccountId = config('services.meta.ad_account_id');

        if (empty($this->adAccountId)) {
            Log::error("MetaAdsService: adAccountId is not set in configuration.");
            throw new \Exception("Meta Ads adAccountId is not configured.");
        }

        if (empty($this->accessToken)) {
            Log::error("MetaAdsService: accessToken is not set in configuration.");
            throw new \Exception("Meta Ads accessToken is not configured.");
        }

        // Log to confirm the version of MetaAdsService being used
        Log::info("Using MetaAdsService version: Updated to remove campaign-level insights");
    }

    public function getCampaigns($date)
    {
        $response = Http::get("https://graph.facebook.com/v19.0/{$this->adAccountId}/campaigns", [
            'access_token' => $this->accessToken,
            'fields' => 'id,name',
            'limit' => 4,
        ]);

        $responseData = $response->json();
        Log::info("Campaigns API Response for {$this->adAccountId} on {$date}: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Campaigns API Error: " . json_encode($responseData['error']));
            return [];
        }

        $campaigns = $responseData['data'] ?? [];
        Log::info("Fetched " . count($campaigns) . " campaigns for date {$date}");

        $result = [];

        foreach ($campaigns as $campaign) {
            $result[] = [
                'campaign_id' => $campaign['id'],
                'name' => $campaign['name'] ?? 'Campaign ' . (count($result) + 1),
                'date' => $date,
            ];
        }

        return $result;
    }

    public function getAdSets($campaignId, $date)
    {
        $response = Http::get("https://graph.facebook.com/v19.0/{$campaignId}/adsets", [
            'access_token' => $this->accessToken,
            'fields' => 'id,name',
        ]);

        $responseData = $response->json();
        Log::info("Ad Sets API Response for {$campaignId}: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Ad Sets API Error: " . json_encode($responseData['error']));
            return [];
        }

        $adSets = $responseData['data'] ?? [];
        $result = [];

        foreach ($adSets as $adSet) {
            $insightsResponse = Http::get("https://graph.facebook.com/v19.0/{$adSet['id']}/insights", [
                'access_token' => $this->accessToken,
                'fields' => 'date_start,date_stop,spend,cpc,impressions,clicks,action_values,actions',
                'time_range' => json_encode(['since' => $date, 'until' => $date]),
                'time_increment' => 1,
            ]);

            $insightsData = $insightsResponse->json();
            Log::info("Insights API Response for ad set {$adSet['id']}: " . json_encode($insightsData));

            if (isset($insightsData['error'])) {
                Log::warning("Insights API error for ad set {$adSet['id']}: " . $insightsData['error']['message']);
                continue;
            }

            $insights = $insightsData['data'][0] ?? null;
            $spend = 0;
            $cpc = 0;
            $impressions = 0;
            $clicks = 0;
            $revenue = 0;

            if ($insights && $insights['date_start'] === $date) {
                Log::info("Insights data for ad set {$adSet['id']} on date {$date}: " . json_encode($insights));
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
                Log::info("No insights data for ad set {$adSet['id']} on date {$date}, using default values.");
            }

            $result[] = [
                'ad_set_id' => $adSet['id'],
                'name' => $adSet['name'] ?? 'Ad Set ' . (count($result) + 1),
                'spend' => $spend,
                'cpc' => $cpc,
                'revenue' => $revenue,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'date' => $date,
            ];
        }

        if (empty($result)) {
            $result[] = [
                'ad_set_id' => 'default_' . $campaignId,
                'name' => 'Ad Set 1',
                'spend' => 0,
                'cpc' => 0,
                'revenue' => 0,
                'impressions' => 0,
                'clicks' => 0,
                'date' => $date,
            ];
        }

        return $result;
    }

    public function getAds($adSetId, $date)
    {
        $response = Http::get("https://graph.facebook.com/v19.0/{$adSetId}/ads", [
            'access_token' => $this->accessToken,
            'fields' => 'id,name,creative{thumbnail_url}',
        ]);

        $responseData = $response->json();
        Log::info("Ads API Response for {$adSetId}: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Ads API Error: " . json_encode($responseData['error']));
            return [];
        }

        $ads = $responseData['data'] ?? [];
        $result = [];

        foreach ($ads as $ad) {
            $insightsResponse = Http::get("https://graph.facebook.com/v19.0/{$ad['id']}/insights", [
                'access_token' => $this->accessToken,
                'fields' => 'date_start,date_stop,spend,cpc,impressions,clicks,action_values,actions',
                'time_range' => json_encode(['since' => $date, 'until' => $date]),
                'time_increment' => 1,
            ]);

            $insightsData = $insightsResponse->json();
            Log::info("Insights API Response for ad {$ad['id']}: " . json_encode($insightsData));

            if (isset($insightsData['error'])) {
                Log::warning("Insights API error for ad {$ad['id']}: " . $insightsData['error']['message']);
                continue;
            }

            $insights = $insightsData['data'][0] ?? null;
            $spend = 0;
            $cpc = 0;
            $impressions = 0;
            $clicks = 0;
            $revenue = 0;

            if ($insights && $insights['date_start'] === $date) {
                Log::info("Insights data for ad {$ad['id']} on date {$date}: " . json_encode($insights));
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
                Log::info("No insights data for ad {$ad['id']} on date {$date}, using default values.");
            }

            $thumbnailUrl = $ad['creative']['thumbnail_url'] ?? null;

            $result[] = [
                'ad_id' => $ad['id'],
                'name' => $ad['name'] ?? 'Ad ' . (count($result) + 1),
                'ad_image' => $thumbnailUrl,
                'spend' => $spend,
                'cpc' => $cpc,
                'revenue' => $revenue,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'date' => $date,
            ];
        }

        if (empty($result)) {
            $result[] = [
                'ad_id' => 'default_' . $adSetId,
                'name' => 'Ad 1',
                'ad_image' => null,
                'spend' => 0,
                'cpc' => 0,
                'revenue' => 0,
                'impressions' => 0,
                'clicks' => 0,
                'date' => $date,
            ];
        }

        return $result;
    }
}
