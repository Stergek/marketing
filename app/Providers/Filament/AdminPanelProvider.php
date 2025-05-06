<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CustomDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\AdvertiserResource;
use App\Filament\Resources\ArtifactResource;
use App\Filament\Widgets\CustomDashboardStats;
use App\Filament\Widgets\KeyMetricsOverview;
use App\Filament\Widgets\TestMetricsOverview;
use App\Filament\Widgets\TestTrendsChart;
use App\Http\Middleware\CheckLayout;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->darkMode(true)
            // ->viteTheme('resources/css/filament/admin/theme.css') // Keep commented
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                CustomDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                KeyMetricsOverview::class,
                TestMetricsOverview::class,
                TestTrendsChart::class,
                // CustomDashboardStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                // 'check-layout',
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            //IMPORTANT: did not add advertisers resources to here.
            //IMPORTANT: did not add advertisers resources to here.
            //IMPORTANT: did not add advertisers resources to here.
            ->resources([
                ArtifactResource::class,
                AdvertiserResource::class,
                // Other resources
            ])
            ->databaseNotifications()
            ;
    }

    public function register(): void
    {
        parent::register();

        // Register the custom middleware
        $this->app['router']->aliasMiddleware('check-layout', CheckLayout::class);
    }
}