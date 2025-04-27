<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'date',
        'spend',
        'cpc',
        'impressions',
        'clicks',
        'inline_link_clicks',
        'revenue',
        'roas',
        'cpm',
        'ctr',
        'last_synced_at',
    ];

    protected $casts = [
        'spend' => 'decimal:2',
        'cpc' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'inline_link_clicks' => 'integer',
        'revenue' => 'decimal:2',
        'roas' => 'decimal:2',
        'cpm' => 'decimal:2',
        'ctr' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}