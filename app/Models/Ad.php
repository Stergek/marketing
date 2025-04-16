<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $fillable = [
        'ad_set_id',
        'ad_id',
        'name',
        'ad_image',
        'spend',
        'cpc',
        'revenue',
        'impressions',
        'clicks',
        'date',
    ];

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }
}
