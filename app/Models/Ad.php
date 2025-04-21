<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_id',
        'status', // Add status to fillable
        'date',
        'ad_set_id',
        'name',
        'ad_image',
        'spend',
        'clicks',
        'impressions',
        'cpc',
        'revenue',
    ];

    public function adSet()
    {
        return $this->belongsTo(AdSet::class);
    }
}
