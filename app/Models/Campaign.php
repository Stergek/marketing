<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'date',
        'ad_account_id',
        'name',
        'spend',
        'clicks',
        'impressions',
        'cpc',
        'revenue',
    ];

    public function adSets()
    {
        return $this->hasMany(AdSet::class);
    }

    public function hasActiveAds()
    {
        return $this->adSets()
            ->whereHas('ads', function ($query) {
                $query->where('status', 'ACTIVE');
            })
            ->exists();
    }
}
