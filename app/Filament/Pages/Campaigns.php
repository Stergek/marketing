<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\Campaign;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class Campaigns extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.campaigns';

    public $selectedDate;
    protected $adAccountId = 'act_1124601191667195'; // Hardcoded ad account ID

    public function mount()
    {
        $maxDate = Campaign::where('ad_account_id', $this->adAccountId)
            ->max('date');
        $this->selectedDate = $maxDate && \Carbon\Carbon::parse($maxDate)->lte(now())
            ? $maxDate
            : now()->toDateString();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Campaign::query()
                    ->where('ad_account_id', $this->adAccountId)
                    ->when($this->selectedDate, fn ($query) => $query->where('date', $this->selectedDate))
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Campaign Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('spend')
                    ->label('Spent')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cpc')
                    ->label('CPC')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('roas')
                    ->label('ROAS')
                    ->getStateUsing(fn ($record) => $record->spend > 0 ? number_format($record->revenue / $record->spend, 2) : 0)
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(),
                TextColumn::make('cpm')
                    ->label('CPM')
                    ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->spend / $record->impressions) * 1000, 2) : 0)
                    ->money('usd')
                    ->color(function ($state) {
                        if ($state <= 10) return 'success';
                        if ($state <= 20) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(),
                TextColumn::make('ctr')
                    ->label('CTR (%)')
                    ->getStateUsing(fn ($record) => $record->impressions > 0 ? number_format(($record->clicks / $record->impressions) * 100, 2) : 0)
                    ->color(function ($state) {
                        if ($state >= 2) return 'success';
                        if ($state >= 1) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Select')
                            ->maxDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $this->selectedDate = $state;
                                if ($state) {
                                    $set('tableFilters.date.date', $state);
                                }
                            }),
                    ]),
            ])
            ->actions([])
            ->emptyStateHeading('No campaigns found')
            ->emptyStateDescription($this->selectedDate ? 'No data for the selected date.' : 'Please select a date to view campaigns.');
    }
}