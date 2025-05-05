<?php
// app/Models/MetaAd.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAd extends Model
{
    protected $fillable = [
        'advertiser_id', 'ad_id', 'ad_snapshot_url', 'creative_body', 'cta',
        'start_date', 'active_duration', 'media_type', 'impressions', 'platforms'
    ];

    protected $casts = [
        'platforms' => 'array',
        'start_date' => 'date',
    ];

    public function advertiser()
    {
        return $this->belongsTo(Advertiser::class);
    }
}