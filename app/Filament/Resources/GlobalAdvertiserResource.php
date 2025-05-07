<?php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use App\Models\Advertiser;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\GlobalAdvertiserResource\Pages;

class GlobalAdvertiserResource extends Resource
{
    protected static ?string $model = Advertiser::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Global Advertisers';
    protected static ?string $navigationGroup = 'Admin';

    public static function canAccess(): bool
    {
        return Auth::user()->role === 'admin'; // Restrict to admin role
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('page_id')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The unique Page ID used for API data fetching.'),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Advertiser Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('page_id')
                    ->label('Page ID')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Advertiser')
                    ->modalHeading('Create New Advertiser'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGlobalAdvertisers::route('/'),
            'create' => Pages\CreateGlobalAdvertiser::route('/create'),
            'edit' => Pages\EditGlobalAdvertiser::route('/{record}/edit'),
        ];
    }
}