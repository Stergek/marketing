<?php
// app/Filament/Resources/AdvertiserResource.php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use App\Models\Advertiser;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use App\Filament\Resources\AdvertiserResource\Pages;
use Illuminate\Support\Facades\Log;

class AdvertiserResource extends Resource
{
    protected static ?string $model = Advertiser::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('page_id')->required()->numeric()->unique(),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        Log::info('Loading AdvertiserResource table');
        return $table
            ->query(Advertiser::query()->with(['ads', 'history']))
            ->columns([
                TextColumn::make('name')
                    ->label('Advertisers')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('active_ads_count')
                    ->label('Ads')
                    ->formatStateUsing(function ($record) {
                        Log::info("Processing record: {$record->name}, ads relation: " . ($record->relationLoaded('ads') ? 'loaded' : 'not loaded'));
                        $ads = $record->ads;
                        $count = $ads ? $ads->count() : 0;
                        Log::info("Ads count for {$record->name}: $count");
                        return $count ?: '0';
                    }),
                TextColumn::make('ad_types')
                    ->label('Type')
                    ->formatStateUsing(function ($record) {
                        Log::info("Processing record: {$record->name}, ads relation: " . ($record->relationLoaded('ads') ? 'loaded' : 'not loaded'));
                        $ads = $record->ads;
                        $total = $ads ? $ads->count() : 0;
                        if (!$total) return 'N/A';
                        $video = $ads ? $ads->where('media_type', 'video')->count() : 0;
                        $result = sprintf('%d%% Video', round($video / $total * 100));
                        Log::info("Type for {$record->name}: $result");
                        return $result;
                    }),
                TextColumn::make('latest_ad')
                    ->label('Latest')
                    ->formatStateUsing(function ($record) {
                        Log::info("Processing record: {$record->name}, ads relation: " . ($record->relationLoaded('ads') ? 'loaded' : 'not loaded'));
                        $ads = $record->ads;
                        $latest = $ads ? $ads->sortByDesc('start_date')->first() : null;
                        if (!$latest) return 'N/A';
                        $result = $latest->start_date->format('Y-m-d');
                        Log::info("Latest for {$record->name}: $result");
                        return $result;
                    }),
                TextColumn::make('longest_active_ad')
                    ->label('Longest Active Ad')
                    ->formatStateUsing(function ($record) {
                        Log::info("Processing record: {$record->name}, ads relation: " . ($record->relationLoaded('ads') ? 'loaded' : 'not loaded'));
                        $ads = $record->ads;
                        $maxDuration = $ads ? $ads->max('active_duration') : 0;
                        $result = $maxDuration ? "$maxDuration days" : 'N/A';
                        Log::info("Longest for {$record->name}: $result");
                        return $result;
                    }),
                TextColumn::make('updated_at')
                    ->label('Last Fetched')
                    ->dateTime(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->modalContent(fn ($record) => view('filament.modals.advertiser-ads', ['advertiser' => $record]))
                    ->modalHeading(fn ($record) => "Ads for {$record->name}")
                    ->modalWidth('5xl'),
                Action::make('fetch')
                    ->label('Fetch')
                    ->action(fn ($record) => Artisan::call('ads:fetch-advertiser', ['page_id' => $record->page_id]))
                    ->successNotificationTitle('Ads fetched successfully'),
            ])
            ->filters([
                //
            ])
            ->recordAction(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisers::route('/'),
            'create' => Pages\CreateAdvertiser::route('/create'),
            'edit' => Pages\EditAdvertiser::route('/{record}/edit'),
        ];
    }
}