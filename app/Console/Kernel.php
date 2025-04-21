<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SyncMetaCampaigns;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $date = now()->toDateString(); // Sync for today
            $adAccountId = 'act_1124601191667195'; // Must match Campaigns.php
            SyncMetaCampaigns::dispatch($date, $adAccountId);
        })->dailyAt('02:00'); // Run daily at 2:00 AM
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}

// Test the Changes:
// Run the scheduled task manually to populate the database:

// php artisan schedule:run

// Or use the console command if you created it:

// php artisan sync:campaigns 2025-04-21 1124601191667195