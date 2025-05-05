<?php
// app/Filament/Resources/AdvertiserResource/Pages/EditAdvertiser.php
namespace App\Filament\Resources\AdvertiserResource\Pages;

use App\Filament\Resources\AdvertiserResource;
use Filament\Resources\Pages\EditRecord;

class EditAdvertiser extends EditRecord
{
    protected static string $resource = AdvertiserResource::class;
}