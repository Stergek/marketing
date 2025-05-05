<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artifact extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'artifact_id',
        'latest_version',
        'description',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }
}