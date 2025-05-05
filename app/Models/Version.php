<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
        'artifact_id',
        'version_id',
        'update_number',
        'description',
    ];

    public function artifact()
    {
        return $this->belongsTo(Artifact::class);
    }
}