<?php
// app/Models/MetaAdHistory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdHistory extends Model
{
    protected $table = 'meta_ads_history';
    protected $fillable = ['advertiser_id', 'date', 'active_ads_count'];

    public function advertiser()
    {
        return $this->belongsTo(Advertiser::class);
    }
}