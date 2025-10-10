<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackfillState extends Model
{
    protected $table = 'backfill_state';
    
    protected $fillable = [
        'process_name',
        'last_page',
        'last_plant_id',
        'total_pages',
        'processed_items',
        'is_completed',
        'started_at',
        'last_checkpoint_at',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_completed' => 'boolean',
        'started_at' => 'datetime',
        'last_checkpoint_at' => 'datetime',
    ];

    /**
     * Récupère ou crée l'état du backfill pour un processus
     */
    public static function getOrCreate(string $processName): self
    {
        return self::firstOrCreate(
            ['process_name' => $processName],
            [
                'last_page' => 0,
                'processed_items' => 0,
                'is_completed' => false,
                'started_at' => now(),
            ]
        );
    }

    /**
     * Met à jour le checkpoint avec les nouvelles données
     */
    public function updateCheckpoint(int $page, ?int $plantId = null, array $metadata = []): void
    {
        $this->update([
            'last_page' => $page,
            'last_plant_id' => $plantId,
            'processed_items' => $this->processed_items + 1,
            'last_checkpoint_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);
    }

    /**
     * Marque le backfill comme terminé
     */
    public function markCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'last_checkpoint_at' => now(),
        ]);
    }

    /**
     * Remet à zéro l'état pour un nouveau backfill complet
     */
    public function reset(): void
    {
        $this->update([
            'last_page' => 0,
            'last_plant_id' => null,
            'total_pages' => null,
            'processed_items' => 0,
            'is_completed' => false,
            'started_at' => now(),
            'last_checkpoint_at' => null,
            'metadata' => null,
        ]);
    }
}
