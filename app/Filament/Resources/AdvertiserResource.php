<?php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use App\Models\Advertiser;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\AdvertiserResource\Pages;
use Illuminate\Support\Facades\Log;

class AdvertiserResource extends Resource
{
    protected static ?string $model = Advertiser::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function table(Tables\Table $table): Tables\Table
    {
        Log::info('Loading AdvertiserResource table');
        $query = Advertiser::query()->with('ads');
        Log::info('Query built: ' . $query->toSql() . ', count: ' . $query->count());
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name')
                    ->label('Advertisers')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('active_ads_count')
                    ->label('Ads')
                    ->formatStateUsing(function ($record) {
                        $record->load('ads');
                        Log::info("Processing record: {$record->name}, ads loaded: " . ($record->relationLoaded('ads') ? 'yes' : 'no'));
                        $ads = $record->ads;
                        $count = $ads ? $ads->count() : 0;
                        Log::info("Ads count for {$record->name}: $count");
                        return $count ?: '0';
                    }),
            ])
            ->recordAction(null) // Disable row actions
            ->recordUrl(null) // Disable row URL (clickability)
            ->paginated([5]); // Explicitly set pagination to 5
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisers::route('/'),
        ];
    }
}
