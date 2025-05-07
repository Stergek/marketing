<div class="p-4">
    <h3 class="text-lg font-semibold mb-2">Ad Summary for {{ $advertiser->name }}</h3>
    @if ($advertiser->ads->isEmpty())
        <p class="text-sm text-gray-600">No ads available.</p>
    @else
        <div class="space-y-4">
            <h4 class="text-md font-medium">Top 5 Longest Active Ads</h4>
            <ul class="space-y-2">
                @foreach ($advertiser->ads->sortByDesc('active_duration')->take(5) as $ad)
                    <li>
                        <strong>Ad ID: {{ $ad->ad_id }}</strong>
                        <p>CTA: {{ $ad->cta ?? 'N/A' }} | Duration: {{ $ad->active_duration ?? 'N/A' }} days</p>
                        @if ($ad->ad_snapshot_url)
                            <img src="{{ $ad->ad_snapshot_url }}" alt="Ad Preview" class="w-32 h-32 object-cover mt-1">
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <p class="mt-4 text-sm text-gray-600">Click <a href="#" class="text-blue-500">here</a> for detailed view (to be implemented).</p>
</div>