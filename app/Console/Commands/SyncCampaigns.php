<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncMetaCampaigns;

class SyncCampaigns extends Command
{
    protected $signature = 'sync:campaigns {date} {adAccountId}';
    protected $description = 'Sync Meta campaigns for a specific date and ad account';

    public function handle()
    {
        $date = $this->argument('date');
        $adAccountId = $this->argument('adAccountId');

        SyncMetaCampaigns::dispatch($date, $adAccountId);

        $this->info("Dispatched SyncMetaCampaigns job for date: {$date}, ad account: {$adAccountId}");
    }
}