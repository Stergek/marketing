<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'ad_account_id',
        'name',
        'date',
        'spend',
        'cpc',
        'impressions',
        'clicks',
        'inline_link_clicks',
        'inline_link_click_ctr',
        'revenue',
        'last_synced_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'spend' => 'decimal:2',
        'cpc' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'inline_link_clicks' => 'integer',
        'inline_link_click_ctr' => 'decimal:2',
        'revenue' => 'decimal:2',
        'date' => 'date',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}