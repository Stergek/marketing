<?php
// app/Livewire/AdvertiserAdsTable.php
namespace App\Livewire;

use Livewire\Component;
use Filament\Tables;
use App\Models\Advertiser;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Contracts\TranslatableContentDriver;

class AdvertiserAdsTable extends Component implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    public Advertiser $advertiser;

    protected function getTableQuery()
    {
        return $this->advertiser->ads()->where('start_date', '<=', now());
    }

    protected function getTableColumns(): array
    {
        return [
            ImageColumn::make('ad_snapshot_url')
                ->label('Ad Preview')
                ->width(100)
                ->height(100)
                ->defaultImageUrl('https://via.placeholder.com/100'),
            TextColumn::make('ad_id')->label('Ad ID')->searchable(),
            TextColumn::make('creative_body')
                ->label('Creative Text')
                ->limit(50)
                ->tooltip(fn ($record) => $record->creative_body),
            BadgeColumn::make('cta')
                ->label('CTA')
                ->colors([
                    'primary' => 'Shop Now',
                    'success' => 'Learn More',
                    'warning' => 'Sign Up',
                ]),
            TextColumn::make('start_date')->label('Start Date')->date(),
            TextColumn::make('active_duration')->label('Duration')->suffix(' days'),
            TextColumn::make('media_type')->label('Media Type'),
            TextColumn::make('impressions')->label('Impressions'),
            TextColumn::make('platforms')->label('Platforms')->formatStateUsing(fn ($state) => implode(', ', $state)),
        ];
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null; // No translatable content needed
    }

    public function render()
    {
        return view('livewire.filament-table');
    }
}