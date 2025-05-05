<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TestCustom extends Page
{
    protected static string $view = 'filament.pages.test-custom';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $title = 'Test Custom Page';

    protected static ?string $slug = 'test-custom';

    protected static ?int $navigationSort = 1;
}