<?php
namespace App\Jobs;

use App\Models\Advertiser;
use App\Models\AdvertiserAdCount;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecordAdvertiserAdCounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    public function __construct($date = null)
    {
        $this->date = $date ? Carbon::parse($date) : now();
    }

    public function handle()
    {
        $date = $this->date->toDateString();
        Log::info("Recording advertiser ad counts for date: {$date}");

        $advertisers = Advertiser::with('ads')->get();
        foreach ($advertisers as $advertiser) {
            $activeAdCount = $advertiser->ads->where('start_date', '<=', $this->date)->count();
            AdvertiserAdCount::updateOrCreate(
                [
                    'advertiser_id' => $advertiser->id,
                    'date' => $date,
                ],
                [
                    'active_ad_count' => $activeAdCount,
                ]
            );
            Log::info("Recorded ad count for advertiser {$advertiser->name}: {$activeAdCount} on {$date}");
        }
    }
}