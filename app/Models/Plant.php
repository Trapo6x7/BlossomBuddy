<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plant extends Model
{
    protected $table = 'plant';

    protected $fillable = [
        'common_name',
        'french_name',
        'alternative_names',
        'family_french',
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
        'alternative_names' => 'array',
    ];

    /**
     * The users that belong to the Plant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_plants', 'plant_id', 'user_id')
                    ->withPivot('city', 'last_watered_at', 'next_watering_at', 'watering_preferences', 'latitude', 'longitude', 'location_name')
                    ->withTimestamps();
    }

    /**
     * Calcule la prochaine date d'arrosage basée sur la fréquence et la dernière fois arrosée
     */
    public function calculateNextWateringDate(\DateTime $lastWateredAt = null): \DateTime
    {
        $lastWatered = $lastWateredAt ?? new \DateTime();
        
        // Récupérer la fréquence d'arrosage depuis watering_general_benchmark
        $benchmark = $this->watering_general_benchmark;
        $frequencyDays = 7; // Par défaut hebdomadaire
        
        if (isset($benchmark['frequency'])) {
            $frequency = strtolower($benchmark['frequency']);
            $frequencyDays = match($frequency) {
                'daily' => 1,
                'every 2 days' => 2,
                'every 3 days' => 3,
                'twice a week' => 3, // Environ 3-4 jours
                'weekly' => 7,
                'every 10 days' => 10,
                'biweekly', 'every 2 weeks' => 14,
                'monthly' => 30,
                default => 7
            };
        }
        
        // Si on a une fréquence numérique directe (en jours)
        if (isset($this->watering_frequency) && is_numeric($this->watering_frequency)) {
            $frequencyDays = (int) $this->watering_frequency;
        }
        
        $nextWatering = clone $lastWatered;
        $nextWatering->modify("+{$frequencyDays} days");
        
        return $nextWatering;
    }

}
