<?php

namespace App\Providers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */

     public function boot()
     {
        /*  DB::listen(function ($query) {
             // Skip logging for queue-related queries
             if (str_contains($query->sql, 'from `jobs`') || str_contains($query->sql, 'from `cache` where `key` in (\'laravel_cache_illuminate:queue:restart\')')) {
                 return;
             }
             Log::info("Query Time: {$query->time}ms", ['sql' => $query->sql, 'bindings' => $query->bindings]);
         }); */
         \Livewire\Livewire::component('custom-dashboard-table', \App\Livewire\CustomDashboardTable::class);

     }

}