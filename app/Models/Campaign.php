<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'spend',
        'cpc',
        'revenue',
        'impressions',
        'clicks',
        'date',
    ];

    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class);
    }
}
