<x-filament-panels::page>
    <!-- Filter Section -->
    <div class="mb-6">
        <x-filament-panels::form wire:model.live="filters">
            {{ $this->filtersForm }}
        </x-filament-panels::form>
    </div>

    <!-- Full Metrics -->
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Key Metrics Summary</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->getStats() as $stat)
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                    <x-filament::icon
                        :icon="$stat['icon']"
                        class="h-6 w-6 {{ $stat['color'] === 'success' ? 'text-green-500' : 'text-red-500' }}"
                    />
                </div>
                <p class="mt-2 text-sm {{ $stat['color'] === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $stat['description'] }}
                </p>
            </x-filament::card>
        @endforeach
    </div>
</x-filament-panels::page>