<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultImage extends Model
{
    protected $table = 'default_image';

    protected $fillable = [
        'licence',
        'licence_name',
        'licence_url',
        'original_url',
        'regular_url',
        'medium_url',
        'small_url',
        'thumbnail'
    ];

    /**
     * Get the plants that use this default image
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function plants()
    {
        return $this->hasOne(Plant::class, 'default_image_id', 'id');
    }
}
