<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plant_id',
        'city',
        'last_watered_at',
        'next_watering_at',
        'watering_preferences'
    ];

    protected $casts = [
        'last_watered_at' => 'datetime',
        'next_watering_at' => 'datetime',
        'watering_preferences' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
