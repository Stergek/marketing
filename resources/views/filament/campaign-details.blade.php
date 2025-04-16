<div class="campaign-details-modal" style="display: flex; flex-direction: column; align-items: center; text-align: center;">
    @foreach($data as $item)
        <div class="level-section" style="margin-bottom: 20px; width: 100%; max-width: 800px;">
            <h3 style="margin-bottom: 10px;">{{ $item['level'] }}: {{ $item['name'] }}</h3>

            <table style="border-collapse: collapse; width: 100%; margin: 0 auto; border: 1px solid #ddd;">
                <thead>
                    <tr style="background-color: #f4f4f4;">
                        <th style="border: 1px solid #ddd; padding: 8px;">Date Range</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Spend</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">CPC</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">ROAS</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">CPM</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">CTR</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">Today</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['spend'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpc'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['roas'], 2) }}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpm'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['ctr'], 2) }}%</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">Last 7 Days</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['spend_7d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpc_7d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['roas_7d'], 2) }}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpm_7d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['ctr_7d'], 2) }}%</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">Last 14 Days</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['spend_14d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpc_14d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['roas_14d'], 2) }}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpm_14d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['ctr_14d'], 2) }}%</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">Last 30 Days</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['spend_30d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpc_30d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['roas_30d'], 2) }}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['cpm_30d'], 2) }} USD</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($item['ctr_30d'], 2) }}%</td>
                    </tr>
                </tbody>
            </table>

            @if($item['level'] === 'Ad' && $item['ad_image'])
                <div class="ad-image" style="margin-top: 10px;">
                    <img src="{{ $item['ad_image'] }}" alt="Ad Image" style="max-width: 200px; max-height: 400px; width: auto; height: auto; display: block; margin: 0 auto; object-fit: contain;">
                </div>
            @endif
        </div>
    @endforeach
</div>
