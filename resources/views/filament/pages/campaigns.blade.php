<x-filament-panels::page>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Total Spend</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-100">${{ $this->getStats()['total_spend'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Date: {{ $this->getStats()['selected_date'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Total Ads</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-100">{{ $this->getStats()['total_ads'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Average CPC</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-100">${{ $this->getStats()['average_cpc'] }}</p>
        </div>
    </div>

    <!-- Table -->
    {{ $this->table }}
</x-filament-panels::page>
