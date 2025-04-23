<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncMetaCampaigns;

class SyncCampaigns extends Command
{
    protected $signature = 'sync:campaigns {date}';
    protected $description = 'Sync Meta campaigns for a specific date';

    public function handle()
    {
        $date = $this->argument('date');
        SyncMetaCampaigns::dispatch($date);
        $this->info("Dispatched SyncMetaCampaigns job for date: {$date}");
    }
}