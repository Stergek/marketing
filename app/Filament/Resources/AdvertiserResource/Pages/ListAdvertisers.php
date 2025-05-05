<?php
// app/Filament/Resources/AdvertiserResource/Pages/ListAdvertisers.php
namespace App\Filament\Resources\AdvertiserResource\Pages;

use App\Filament\Resources\AdvertiserResource;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisers extends ListRecords
{
    protected static string $resource = AdvertiserResource::class;
}
