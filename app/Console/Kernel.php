<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SyncMetaCampaigns;
use App\Console\Commands\FetchAdLibraryData; // Assuming you create this command

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Existing campaign sync job
        $schedule->call(function () {
            $date = now()->toDateString(); // Always YYYY-MM-DD
            SyncMetaCampaigns::dispatch($date);
        })->dailyAt('02:00');
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}