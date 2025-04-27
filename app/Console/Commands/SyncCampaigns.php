<?php

namespace App\Console\Commands;

use App\Jobs\SyncMetaCampaigns;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCampaigns extends Command
{
    protected $signature = 'sync:campaigns {date?} {--force}';
    protected $description = 'Sync campaigns data from Meta Ads for a specific date. Use --force to sync regardless of rules.';

    public function handle()
    {
        $setting = Setting::first();
        $adAccountId = $setting ? $setting->ad_account_id : null;

        if (empty($adAccountId)) {
            $this->error('Ad Account ID is not set. Please configure it in the Settings page.');
            Log::error('SyncCampaigns: Ad Account ID is not set in settings.');
            return 1;
        }

        $date = $this->argument('date') ?? now()->toDateString();
        $force = $this->option('force');

        try {
            $parsedDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            $this->error('Invalid date format. Please use YYYY-MM-DD.');
            Log::error("SyncCampaigns: Invalid date format provided: {$date}");
            return 1;
        }

        $this->info("Dispatching sync job for date: {$parsedDate}, ad_account_id: {$adAccountId}, force: " . ($force ? 'true' : 'false'));
        Log::info("Dispatching sync job", [
            'date' => $parsedDate,
            'ad_account_id' => $adAccountId,
            'force' => $force,
        ]);

        SyncMetaCampaigns::dispatch($parsedDate, $force);

        $this->info('Sync job dispatched successfully.');
        return 0;
    }
}