<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertiserAdCount extends Model
{
    protected $fillable = ['advertiser_id', 'date', 'active_ad_count'];

    protected $casts = [
        'date' => 'date',
    ];

    public function advertiser()
    {
        return $this->belongsTo(Advertiser::class);
    }
}