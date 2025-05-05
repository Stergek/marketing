<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtifactResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArtifactResource extends Resource
{
    protected static ?string $model = \App\Models\Artifact::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Artifacts';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latest_version')
                    ->label('Latest Version')
                    ->formatStateUsing(fn ($state) => $state ?? 'None')
                    ->wrap(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('file_path')
                    ->label('File Path')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('artifact_id')
                    ->label('Artifact ID')
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('folder')
                    ->options([
                        'app/Models' => 'app/Models',
                        'app/Filament' => 'app/Filament',
                        'resources/views' => 'resources/views',
                        'app/Http' => 'app/Http',
                        'database' => 'database',
                    ])
                    ->label('Folder')
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->where('file_path', 'like', $data['value'] . '%');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_versions')
                    ->label('View Versions')
                    ->modalHeading(fn ($record) => "Versions for {$record->file_name}")
                    ->modalContent(function ($record) {
                        $versions = $record->versions;
                        return view('filament.resources.artifact-resource.version-details', [
                            'versions' => $versions,
                            'artifact_id' => $record->artifact_id,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('file_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtifacts::route('/'),
        ];
    }
}