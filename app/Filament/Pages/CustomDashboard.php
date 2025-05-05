<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;

class CustomDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Custom Dashboard';
    protected static ?string $slug = 'custom-dashboard';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.custom-dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\CustomDashboardStats::class,
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'total_spend' => '$1500.00',
                'new_customers' => '250',
            ])
            ->schema([
                TextEntry::make('total_spend')
                    ->label('Total Spend This Month')
                    ->color('success'),
                TextEntry::make('new_customers')
                    ->label('New Customers This Month')
                    ->color('info'),
            ]);
    }
}