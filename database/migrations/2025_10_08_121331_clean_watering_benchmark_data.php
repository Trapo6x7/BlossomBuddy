<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nettoyer complètement les données JSON mal encodées
        $plants = DB::table('plant')->get();
        
        foreach ($plants as $plant) {
            $benchmark = $plant->watering_general_benchmark;
            
            if (is_string($benchmark)) {
                echo "Traitement plante ID {$plant->id}: {$plant->common_name}\n";
                echo "Avant: {$benchmark}\n";
                
                // Décoder récursivement jusqu'à obtenir un array
                $decoded = $benchmark;
                $iterations = 0;
                
                while (is_string($decoded) && $iterations < 5) {
                    $newDecoded = json_decode($decoded, true);
                    if ($newDecoded === null && json_last_error() !== JSON_ERROR_NONE) {
                        break; // Arrêter si on ne peut plus décoder
                    }
                    $decoded = $newDecoded;
                    $iterations++;
                }
                
                // Si on a réussi à obtenir un array avec les bonnes clés
                if (is_array($decoded) && isset($decoded['value']) && isset($decoded['unit'])) {
                    // Nettoyer la valeur si elle est encore encodée
                    $value = $decoded['value'];
                    if (is_string($value)) {
                        // Supprimer les échappements en trop
                        $value = str_replace('\\"', '"', $value);
                        $value = trim($value, '"'); // Supprimer les guillemets en début/fin
                        
                        // Si c'est encore du JSON, le décoder
                        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || 
                            json_decode($value) !== null) {
                            $decodedValue = json_decode($value);
                            if ($decodedValue !== null) {
                                $value = $decodedValue;
                            }
                        }
                    }
                    
                    $cleanData = [
                        'value' => $value,
                        'unit' => $decoded['unit']
                    ];
                    
                    echo "Après: " . json_encode($cleanData) . "\n";
                    
                    // Mettre à jour avec les données nettoyées
                    DB::table('plant')
                        ->where('id', $plant->id)
                        ->update(['watering_general_benchmark' => json_encode($cleanData)]);
                        
                    echo "✓ Corrigé\n\n";
                } else {
                    echo "✗ Impossible de corriger - format inattendu\n\n";
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback possible pour cette migration de nettoyage
    }
};
