<?php

namespace App\Filament\Resources\ArtifactResource\Pages;

use App\Filament\Resources\ArtifactResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListArtifacts extends ListRecords
{
    protected static string $resource = ArtifactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}