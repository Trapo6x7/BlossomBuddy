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
        // Corriger les données JSON mal encodées dans la colonne watering_general_benchmark
        $plants = DB::table('plant')->get();
        
        foreach ($plants as $plant) {
            $benchmark = $plant->watering_general_benchmark;
            
            if (is_string($benchmark)) {
                // Essayer de décoder le JSON
                $decoded = json_decode($benchmark, true);
                
                // Si on a encore un string après décodage, c'est un double encodage
                if (is_array($decoded) && isset($decoded['value']) && is_string($decoded['value'])) {
                    // Si la valeur contient des échappements JSON, la décoder
                    if (strpos($decoded['value'], '\\"') !== false || (strpos($decoded['value'], '"') === 0 && substr($decoded['value'], -1) === '"')) {
                        $decodedValue = json_decode($decoded['value']);
                        if ($decodedValue !== null) {
                            $decoded['value'] = $decodedValue;
                            
                            // Mettre à jour la ligne avec la valeur corrigée
                            DB::table('plant')
                                ->where('id', $plant->id)
                                ->update(['watering_general_benchmark' => json_encode($decoded)]);
                                
                            echo "Corrigé plante ID {$plant->id}: {$plant->common_name}\n";
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cette migration ne peut pas être annulée car on ne peut pas retrouver l'état précédent
        // Les données corrompues d'origine ne peuvent pas être restaurées
    }
};
