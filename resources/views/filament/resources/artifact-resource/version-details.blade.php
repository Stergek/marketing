<div class="p-4">
    <h3 class="text-lg font-semibold mb-2">Version History</h3>
    @if ($versions->isEmpty())
        <p class="text-sm text-gray-600">No versions available.</p>
    @else
        <ul class="space-y-4">
            @foreach ($versions as $version)
                <li>
                    <strong>Update {{ $version->update_number }}</strong>: {{ $version->version_id }}
                    <p class="text-sm text-gray-600">{{ $version->description }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</div>