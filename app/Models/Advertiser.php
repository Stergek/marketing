<?php
// app/Models/Advertiser.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertiser extends Model
{
    protected $fillable = ['name', 'page_id', 'notes'];

    public function ads()
    {
        return $this->hasMany(MetaAd::class);
    }

    public function history()
    {
        return $this->hasMany(MetaAdHistory::class);
    }
}