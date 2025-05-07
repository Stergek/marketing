<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertiser;
use App\Models\MetaAd;

class AdvertiserSeeder extends Seeder
{
    public function run()
    {
        $advertisers = [
            ['name' => 'Trendyol', 'page_id' => '100000123456789', 'notes' => 'Main competitor in Turkey'],
            ['name' => 'Manta', 'page_id' => '100000987654321', 'notes' => 'Sleep mask brand'],
            ['name' => 'Baboon to the Moon', 'page_id' => '100000111222333', 'notes' => 'Backpack brand'],
            ['name' => 'Shein', 'page_id' => '100000444555666', 'notes' => 'Fast fashion'],
            ['name' => 'LC Waikiki', 'page_id' => '100000777888999', 'notes' => 'Turkish apparel'],
        ];

        foreach ($advertisers as $data) {
            $advertiser = Advertiser::create($data);
            $adCount = rand(15, 40);

            for ($i = 0; $i < $adCount; $i++) {
                MetaAd::create([
                    'advertiser_id' => $advertiser->id,
                    'ad_id' => uniqid('ad_'),
                    'ad_snapshot_url' => "https://fake-ad-image.com/{$advertiser->page_id}/{$i}.jpg",
                    'creative_body' => "Shop our latest collection! Discount up to 20% off. Ad #{$i}",
                    'cta' => collect(['Shop Now', 'Learn More', 'Sign Up'])->random(),
                    'start_date' => now()->subDays(rand(1, 180)),
                    'active_duration' => rand(1, 180),
                    'media_type' => collect(['video', 'image'])->random(),
                    'type' => collect(['traffic', 'conversion'])->random(), // Add random type
                    'impressions' => rand(1000, 100000),
                    'platforms' => json_encode(collect(['Facebook', 'Instagram', 'Messenger'])->random(rand(1, 3))->toArray()),
                ]);
            }
        }
    }
}