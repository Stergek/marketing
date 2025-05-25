<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advertiser;
use App\Services\AdLibraryService;

class FetchAdLibraryData extends Command
{
    protected $signature = 'advertisers:fetch-adlibrary';
    protected $description = 'Fetch ad data from Meta Ad Library API for advertisers';

    public function handle()
    {
        $adLibraryService = new AdLibraryService();
        $advertisers = Advertiser::all();

        foreach ($advertisers as $advertiser) {
            if ($advertiser->page_id) {
                $ads = $adLibraryService->getAdsByPage($advertiser->page_id);
                \Log::info("Fetched {$advertiser->ad_library_ads_count} ads for advertiser {$advertiser->name}");
            }
        }

        $this->info('Ad Library data fetched successfully.');
    }
}