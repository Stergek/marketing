<?php
namespace App\Filament\Resources\GlobalAdvertiserResource\Pages;

use App\Filament\Resources\GlobalAdvertiserResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListGlobalAdvertisers extends ListRecords
{
    protected static string $resource = GlobalAdvertiserResource::class;

    public function mount(): void
    {
        Log::info('Mounting ListGlobalAdvertisers page');
        parent::mount();
    }
}