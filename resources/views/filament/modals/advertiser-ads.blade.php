@props(['advertiser'])

<x-filament::modal>
    <div class="p-4">
        <h2 class="text-xl font-semibold mb-4">Active Ads for {{ $advertiser->name }}</h2>
        <p class="mb-4">
            <a href="#" class="text-blue-600 hover:underline" onclick="alert('Full page view to be implemented later')">View Full Page</a>
        </p>
        <livewire:advertiser-ads-table :advertiser="$advertiser" />
    </div>
</x-filament::modal>