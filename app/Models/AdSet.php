<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSet extends Model
{
    protected $fillable = [
        'campaign_id',
        'ad_set_id',
        'name',
        'spend',
        'cpc',
        'revenue',
        'impressions',
        'clicks',
        'date',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
