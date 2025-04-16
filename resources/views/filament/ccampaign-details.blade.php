<div class="p-4">
    @foreach ($data as $item)
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $item['name'] }}</h3>
            <table class="min-w-full border-collapse border border-gray-200 dark:border-gray-600">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100"></th>
                        <th class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">Today</th>
                        <th class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">Last 7 Days</th>
                        <th class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">Last 14 Days</th>
                        <th class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">Last 30 Days</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($item['level'] === 'Ad' && $item['ad_image'])
                        <tr>
                            <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">Ad Image</td>
                            <td class="border border-gray-200 dark:border-gray-600 px-4 py-2" colspan="4">
                                <img src="{{ $item['ad_image'] }}" alt="Ad Image" class="h-10 w-10 object-cover">
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">CPC</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpc'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpc_7d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpc_14d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpc_30d'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">Spend</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['spend'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['spend_7d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['spend_14d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['spend_30d'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">ROAS</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['roas'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['roas_7d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['roas_14d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['roas_30d'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">CPM</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpm'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpm_7d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpm_14d'], 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">${{ number_format($item['cpm_30d'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">CTR (%)</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['ctr'], 2) }}%</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['ctr_7d'], 2) }}%</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['ctr_14d'], 2) }}%</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($item['ctr_30d'], 2) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
</div>
