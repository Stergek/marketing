@php
    $useCustomLayout = $useCustomLayout ?? true;
@endphp

@extends($useCustomLayout ? 'layouts.custom-layout' : (view()->exists('filament-panels::components.layout') ? 'filament-panels::components.layout' : 'filament::components.layout'))

@section('title', 'Test Custom Page')


@section('content')
    <div class="container mx-auto p-4 max-w-full">
        <h1 class="text-2xl font-bold mb-4">Test Custom Page</h1>
        <div class="flex flex-row flex-wrap gap-2">
            <!-- Card 1: Total Spend -->
            <div class="custom-card bg-white dark:bg-gray-800 rounded-lg shadow flex items-center justify-center flex-col flex-1 min-w-[150px] max-w-[200px] sm:max-w-[250px] md:max-w-[200px]">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Total Spend</span>
                <span class="text-gray-900 dark:text-gray-100">$1500.00</span>
                <span class="text-green-600 dark:text-green-400">+10.5%</span>
                <span class="text-green-600 dark:text-green-400">↑</span>
            </div>
            <!-- Card 2: Average CPC -->
            <div class="custom-card bg-white dark:bg-gray-800 rounded-lg shadow flex items-center justify-center flex-col flex-1 min-w-[150px] max-w-[200px] sm:max-w-[250px] md:max-w-[200px]">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Average CPC</span>
                <span class="text-gray-900 dark:text-gray-100">$0.60</span>
                <span class="text-red-600 dark:text-red-400">-5.2%</span>
                <span class="text-red-600 dark:text-red-400">↓</span>
            </div>
            <!-- Card 3: Average ROAS -->
            <div class="custom-card bg-white dark:bg-gray-800 rounded-lg shadow flex items-center justify-center flex-col flex-1 min-w-[150px] max-w-[200px] sm:max-w-[250px] md:max-w-[200px]">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Average ROAS</span>
                <span class="text-gray-900 dark:text-gray-100">3.50</span>
                <span class="text-green-600 dark:text-green-400">+12.7%</span>
                <span class="text-green-600 dark:text-green-400">↑</span>
            </div>
            <!-- Card 4: Average CTR -->
            <div class="custom-card bg-white dark:bg-gray-800 rounded-lg shadow flex items-center justify-center flex-col flex-1 min-w-[150px] max-w-[200px] sm:max-w-[250px] md:max-w-[200px]">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Average CTR</span>
                <span class="text-gray-900 dark:text-gray-100">1.25%</span>
                <span class="text-red-600 dark:text-red-400">-3.1%</span>
                <span class="text-red-600 dark:text-red-400">↓</span>
            </div>
            <!-- Card 5: Placeholder -->
            <div class="custom-card bg-white dark:bg-gray-800 rounded-lg shadow flex items-center justify-center flex-col flex-1 min-w-[150px] max-w-[200px] sm:max-w-[250px] md:max-w-[200px]">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Placeholder 1</span>
                <span class="text-gray-900 dark:text-gray-100">$0.00</span>
                <span class="text-gray-600 dark:text-gray-400">0.0%</span>
                <span class="text-gray-600 dark:text-gray-400">→</span>
            </div>
            <!-- Card 6: Placeholder -->
            <div class="custom-card bg-white dark:bg-gray-800 rounded-lg shadow flex items-center justify-center flex-col flex-1 min-w-[150px] max-w-[200px] sm:max-w-[250px] md:max-w-[200px]">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Placeholder 2</span>
                <span class="text-gray-900 dark:text-gray-100">$0.00</span>
                <span class="text-gray-600 dark:text-gray-400">0.0%</span>
                <span class="text-gray-600 dark:text-gray-400">→</span>
            </div>
        </div>
    </div>
@endsection