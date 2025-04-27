<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class MetaAdsService
{
    protected $accessToken;

    public function __construct()
    {
        $setting = Setting::first();
        $this->accessToken = $setting ? $setting->meta_access_token : null;

        if (empty($this->accessToken)) {
            Log::error("MetaAdsService: accessToken is not set in settings.");
            throw new \Exception("Meta Ads accessToken is not configured. Please set it in the Settings page.");
        }

        Log::info("Using MetaAdsService version: Simplified for campaign insights only");
    }

    public function getCampaigns($adAccountId, $date)
    {
        $apiAdAccountId = "act_{$adAccountId}";

        $response = Http::get("https://graph.facebook.com/v22.0/{$apiAdAccountId}/campaigns", [
            'access_token' => $this->accessToken,
            'fields' => 'id,name,status,start_time,stop_time',
            'limit' => 10,
        ]);

        $responseData = $response->json();
        Log::info("Campaigns API Response for {$apiAdAccountId} on {$date}: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Campaigns API Error: " . json_encode($responseData['error']));
            throw new \Exception(json_encode($responseData['error']));
        }

        $campaigns = $responseData['data'] ?? [];
        Log::info("Fetched " . count($campaigns) . " campaigns for date {$date}");

        $batch = [];
        foreach ($campaigns as $campaign) {
            $batch[] = [
                'method' => 'GET',
                'relative_url' => "v22.0/{$campaign['id']}/insights?fields=date_start,date_stop,spend,cpc,impressions,clicks,inline_link_clicks,inline_link_click_ctr,action_values&time_range=" . urlencode(json_encode(['since' => $date, 'until' => $date])) . "&time_increment=1",
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
                $inlineLinkClicks = 0;
                $inlineLinkClickCtr = 0;
                $revenue = 0;

                if ($insights && $insights['date_start'] === $date) {
                    Log::info("Insights data for campaign {$campaign['id']} on date {$date}: " . json_encode($insights));
                    $spend = $insights['spend'] ?? 0;
                    $cpc = $insights['cpc'] ?? 0;
                    $impressions = $insights['impressions'] ?? 0;
                    $clicks = $insights['clicks'] ?? 0;
                    $inlineLinkClicks = $insights['inline_link_clicks'] ?? 0;
                    $inlineLinkClickCtr = $insights['inline_link_click_ctr'] ?? 0;

                    if (isset($insights['action_values'])) {
                        foreach ($insights['action_values'] as $action) {
                            if (in_array($action['action_type'], ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                                $revenue = $action['value'];
                                break;
                            }
                        }
                    }
                } else {
                    Log::info("No insights data for campaign {$campaign['id']} on date {$date}, using default values.", [
                        'status' => $campaign['status'] ?? 'unknown',
                        'start_time' => $campaign['start_time'] ?? 'not set',
                        'stop_time' => $campaign['stop_time'] ?? 'not set',
                    ]);
                }

                $result[] = [
                    'campaign_id' => $campaign['id'],
                    'name' => $campaign['name'] ?? 'Campaign ' . (count($result) + 1),
                    'date' => $date,
                    'spend' => number_format((float)$spend, 2, '.', ''),
                    'cpc' => number_format((float)$cpc, 2, '.', ''),
                    'impressions' => (int)$impressions,
                    'clicks' => (int)$clicks,
                    'inline_link_clicks' => (int)$inlineLinkClicks,
                    'inline_link_click_ctr' => number_format((float)$inlineLinkClickCtr, 2, '.', ''),
                    'revenue' => number_format((float)$revenue, 2, '.', ''),
                ];
            }
        }

        if (empty($result)) {
            $result[] = [
                'campaign_id' => 'default_' . $adAccountId,
                'name' => 'Campaign 1',
                'date' => $date,
                'spend' => '0.00',
                'cpc' => '0.00',
                'impressions' => 0,
                'clicks' => 0,
                'inline_link_clicks' => 0,
                'inline_link_click_ctr' => '0.00',
                'revenue' => '0.00',
            ];
        }

        return $result;
    }

    public function getAccountInsights($adAccountId, $date)
    {
        $apiAdAccountId = "act_{$adAccountId}";

        Log::info("Fetching ad account insights for {$apiAdAccountId} on {$date}");

        $response = Http::get("https://graph.facebook.com/v22.0/{$apiAdAccountId}/insights", [
            'access_token' => $this->accessToken,
            'time_range' => json_encode([
                'since' => $date,
                'until' => $date,
            ]),
            'fields' => 'spend,cpc,inline_link_clicks,inline_link_click_ctr,clicks,action_values,impressions',
            'level' => 'account',
            'action_attribution_windows' => json_encode(['1d_view', '7d_click']),
        ]);

        $responseData = $response->json();
        Log::info("Account Insights API Response for {$apiAdAccountId} on {$date}: " . json_encode($responseData));

        if (isset($responseData['error'])) {
            Log::error("Account Insights API Error: " . json_encode($responseData['error']));
            throw new \Exception(json_encode($responseData['error']));
        }

        if ($response->hasHeader('x-app-usage')) {
            Log::info("API Usage Headers for Account Insights", ['x-app-usage' => $response->header('x-app-usage')]);
        }

        $insightsData = $responseData['data'][0] ?? null;

        if (!$insightsData) {
            Log::info("No insights data for ad account {$apiAdAccountId} on date {$date}, using default values.");
            return [
                'spend' => '0.00',
                'cpc' => '0.00',
                'impressions' => 0,
                'clicks' => 0,
                'inline_link_clicks' => 0,
                'inline_link_click_ctr' => '0.00',
                'revenue' => '0.00',
                'cpm' => '0.00',
                'ctr' => '0.00',
                'roas' => '0.00',
            ];
        }

        $revenue = 0;
        if (isset($insightsData['action_values'])) {
            Log::info("Action values for ad account {$apiAdAccountId} on {$date}: " . json_encode($insightsData['action_values']));
            foreach ($insightsData['action_values'] as $action) {
                if (in_array($action['action_type'], ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                    $revenue = (float)$action['value'];
                    break;
                }
            }
        } else {
            Log::info("No action_values found for ad account {$apiAdAccountId} on {$date}");
        }

        $spend = (float)($insightsData['spend'] ?? 0);
        $impressions = (int)($insightsData['impressions'] ?? 0);
        $clicks = (int)($insightsData['clicks'] ?? 0);
        $inlineLinkClicks = (int)($insightsData['inline_link_clicks'] ?? 0);
        $inlineLinkClickCtr = (float)($insightsData['inline_link_click_ctr'] ?? 0);

        // Fallback for CTR if inline_link_click_ctr is not available
        $ctr = $inlineLinkClickCtr;
        if ($ctr == 0 && $impressions > 0 && $inlineLinkClicks > 0) {
            $ctr = ($inlineLinkClicks / $impressions) * 100;
            Log::info("Calculated CTR for {$apiAdAccountId} on {$date} as fallback: {$ctr}");
        }

        // Calculate CPM manually
        $cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0;

        $insights = [
            'spend' => number_format($spend, 2, '.', ''),
            'cpc' => number_format((float)($insightsData['cpc'] ?? 0), 2, '.', ''),
            'impressions' => $impressions,
            'clicks' => $clicks,
            'inline_link_clicks' => $inlineLinkClicks,
            'inline_link_click_ctr' => number_format($inlineLinkClickCtr, 2, '.', ''),
            'revenue' => number_format($revenue, 2, '.', ''),
            'cpm' => number_format($cpm, 2, '.', ''),
            'ctr' => number_format($ctr, 2, '.', ''),
            'roas' => $spend > 0 ? number_format($revenue / $spend, 2, '.', '') : '0.00',
        ];

        Log::info("Parsed insights for {$apiAdAccountId} on {$date}: " . json_encode($insights));

        return $insights;
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

        if ($response->hasHeader('x-app-usage')) {
            Log::info("API Usage Headers", ['x-app-usage' => $response->header('x-app-usage')]);
        }

        return $responseData;
    }
}