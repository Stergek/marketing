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



// Test the Changes:
// Run the scheduled task manually to populate the database:

// php artisan schedule:run

// Or use the console command if you created it:

// php artisan sync:campaigns 2025-04-21 1124601191667195