<?php
namespace App\Filament\Resources\AdvertiserResource\Pages;

use App\Filament\Resources\AdvertiserResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListAdvertisers extends ListRecords
{
    protected static string $resource = AdvertiserResource::class;

    public function mount(): void
    {
        Log::info('Mounting ListAdvertisers page');
        parent::mount();
    }
}