<?php
// app/Filament/Resources/AdvertiserResource/Pages/CreateAdvertiser.php
namespace App\Filament\Resources\AdvertiserResource\Pages;

use App\Filament\Resources\AdvertiserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvertiser extends CreateRecord
{
    protected static string $resource = AdvertiserResource::class;
}