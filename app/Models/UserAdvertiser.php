<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAdvertiser extends Model
{
    protected $fillable = ['user_id', 'advertiser_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advertiser()
    {
        return $this->belongsTo(Advertiser::class);
    }
}