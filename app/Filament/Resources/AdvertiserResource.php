<?php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use App\Models\Advertiser;
use App\Models\UserAdvertiser;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Filament\Resources\AdvertiserResource\Pages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class AdvertiserResource extends Resource
{
    protected static ?string $model = Advertiser::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function table(Tables\Table $table): Tables\Table
    {
        Log::info('Loading AdvertiserResource table');
        // Query only advertisers associated with the authenticated user
        $user = Auth::user();
        $query = Advertiser::query()
            ->whereIn('id', $user->advertisers()->pluck('advertisers.id'))
            ->with('ads');
        Log::info('Query built: ' . $query->toSql() . ', count: ' . $query->count());
        
        $records = $query->get();
        Log::info('Records fetched: ' . $records->count());
        foreach ($records as $record) {
            Log::info("Record fetched: {$record->name}, Active Ads: {$record->active_ads_count}");
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name')
                    ->label('Advertisers')
                    ->sortable()
                    ->searchable()
                    ->color(fn ($record) => self::getRelativeColor($record->active_ads_count, $records->pluck('active_ads_count'), 'ads')),
                TextColumn::make('active_ads_count')
                    ->label('Ads')
                    ->sortable()
                    ->color(fn ($record) => self::getRelativeColor($record->active_ads_count, $records->pluck('active_ads_count'), 'ads')),
                TextColumn::make('type_percentage')
                    ->label('Type')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        $state = $record->type_percentage;
                        if ($state === 'N/A') {
                            return $state;
                        }
                        $traffic = str_pad((string)$state['traffic'], 4, ' ', STR_PAD_LEFT) . '%';
                        $conversion = str_pad((string)$state['conversion'], 4, ' ', STR_PAD_LEFT) . '%';
                        return "Traffic Conversion<br><div style='text-align: center'><span style='display: inline-block; width: 60px; text-align: center; white-space: pre'>{$traffic}</span><span style='display: inline-block; width: 60px; text-align: center; white-space: pre'>{$conversion}</span></div>";
                    }),
                TextColumn::make('latest_ad_info')
                    ->label('Latest')
                    ->sortable(),
                TextColumn::make('impressions')
                    ->label('Impressions')
                    ->sortable()
                    ->color(fn ($record) => self::getRelativeColor($record->impressions, $records->pluck('impressions'), 'impressions')),
                TextColumn::make('media_type_percentage')
                    ->label('Media Type')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        $state = $record->media_type_percentage;
                        if ($state === 'N/A') {
                            return $state;
                        }
                        $video = str_pad((string)$state['video'], 4, ' ', STR_PAD_LEFT) . '%';
                        $image = str_pad((string)$state['image'], 4, ' ', STR_PAD_LEFT) . '%';
                        return "Video Image<br><div style='text-align: center'><span style='display: inline-block; width: 60px; text-align: center; white-space: pre'>{$video}</span><span style='display: inline-block; width: 60px; text-align: center; white-space: pre'>{$image}</span></div>";
                    }),
                TextColumn::make('ad_count_change')
                    ->label('Change')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->ad_count_change['value'])
                    ->color(fn ($record) => $record->ad_count_change['color']),
            ])
            ->actions([
                Action::make('viewSummary')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn ($record) => "Summary for {$record->name}")
                    ->modalContent(fn ($record) => view('filament.resources.advertiser-resource.summary-modal', [
                        'advertiser' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([
                Action::make('addAdvertisers')
                    ->label('Add Advertisers')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('advertisers')
                            ->label('Select Advertisers')
                            ->options(function () {
                                $user = Auth::user();
                                // Fetch advertisers not already added by the user
                                return Advertiser::whereNotIn('id', $user->advertisers()->pluck('advertisers.id'))
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->multiple()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $user = Auth::user();
                        $advertiserIds = $data['advertisers'];
                        foreach ($advertiserIds as $advertiserId) {
                            UserAdvertiser::create([
                                'user_id' => $user->id,
                                'advertiser_id' => $advertiserId,
                            ]);
                        }
                        Notification::make()
                            ->title('Success')
                            ->body('Advertisers added successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->paginated([5]);
    }

    // Helper method to determine color based on relative value
    private static function getRelativeColor($value, $allValues, $column)
    {
        if ($allValues->isEmpty()) {
            return 'gray';
        }

        $maxValue = $allValues->max();
        $minValue = $allValues->min();
        $range = $maxValue - $minValue;

        if ($range == 0) {
            return 'gray';
        }

        $normalized = ($value - $minValue) / $range;
        if ($normalized >= 0.75) {
            return 'success'; // High value
        } elseif ($normalized >= 0.25) {
            return 'warning'; // Medium value
        } else {
            return 'danger'; // Low value
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisers::route('/'),
        ];
    }
}