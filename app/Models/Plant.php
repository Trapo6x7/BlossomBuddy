<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plant extends Model
{
    protected $table = 'plant';

    protected $fillable = [
        'common_name',
        'ville',
        'watering',
        'watering_general_benchmark',
        'image_url',
        'thumbnail_url',
        'medium_url',
        'regular_url',
        'license',
        'license_name',
        'license_url'
    ];

    // Cast le champ watering_general_benchmark en array pour le JSON
    protected $casts = [
        'watering_general_benchmark' => 'array',
    ];

    /**
     * The users that belong to the Plant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'plant_user', 'plant_id', 'user_id');
    }

}
