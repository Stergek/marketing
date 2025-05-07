<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Advertiser extends Model
{
    protected $fillable = ['name', 'page_id', 'notes'];

    public function ads()
    {
        return $this->hasMany(MetaAd::class);
    }

    public function adCounts()
    {
        return $this->hasMany(AdvertiserAdCount::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_advertisers');
    }

    // Accessor for active ads count
    public function getActiveAdsCountAttribute()
    {
        return $this->ads->count() ?: 0;
    }

    // Accessor for type percentage (rounded, without colors)
    public function getTypePercentageAttribute()
    {
        if ($this->ads->isEmpty()) {
            return 'N/A';
        }

        $totalAds = $this->ads->count();
        $trafficCount = $this->ads->where('type', 'traffic')->count();
        $conversionCount = $this->ads->where('type', 'conversion')->count();

        $trafficPercentage = round(($trafficCount / $totalAds) * 100);
        $conversionPercentage = round(($conversionCount / $totalAds) * 100);

        return [
            'traffic' => $trafficPercentage,
            'conversion' => $conversionPercentage,
        ];
    }

    // Accessor for media type percentage (rounded, without colors)
    public function getMediaTypePercentageAttribute()
    {
        if ($this->ads->isEmpty()) {
            return 'N/A';
        }

        $totalAds = $this->ads->count();
        $videoCount = $this->ads->where('media_type', 'video')->count();
        $imageCount = $this->ads->where('media_type', 'image')->count();

        $videoPercentage = round(($videoCount / $totalAds) * 100);
        $imagePercentage = round(($imageCount / $totalAds) * 100);

        return [
            'video' => $videoPercentage,
            'image' => $imagePercentage,
        ];
    }

    // Accessor for latest ad information (date and count)
    public function getLatestAdInfoAttribute()
    {
        $latestAd = $this->ads->where('start_date', '<=', now())->sortByDesc('start_date')->first();
        $activeAdCount = $this->ads->where('start_date', '<=', now())->count();

        if ($latestAd && $activeAdCount) {
            return $latestAd->start_date->format('Y-m-d') . ' (' . $activeAdCount . ')';
        }
        return 'N/A (0)';
    }

    // Accessor for total impressions
    public function getImpressionsAttribute()
    {
        return $this->ads->where('start_date', '<=', now())->sum('impressions') ?: 0;
    }

    // Accessor for change in ad count (relative to yesterday)
    public function getAdCountChangeAttribute()
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $todayCount = $this->adCounts()->where('date', $today)->first()->active_ad_count ?? $this->active_ads_count;
        $yesterdayCount = $this->adCounts()->where('date', $yesterday)->first()->active_ad_count ?? 0;

        if ($yesterdayCount == 0) {
            return $todayCount > 0 ? ['value' => '+100%', 'color' => 'success'] : ['value' => '0%', 'color' => 'gray'];
        }

        $change = (($todayCount - $yesterdayCount) / $yesterdayCount) * 100;
        $color = $change >= 0 ? 'success' : 'danger';

        return [
            'value' => sprintf('%+.0f%%', $change),
            'color' => $color,
        ];
    }
}