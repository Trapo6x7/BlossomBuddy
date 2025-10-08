<?php

namespace App\Console\Commands;

use App\Models\Plant;
use Illuminate\Console\Command;

class TestAutoTranslation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:test-auto-translation {plant_name : Nom de la plante à tester}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste le système de traduction automatique en créant une plante temporaire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plantName = $this->argument('plant_name');
        
        $this->info("🧪 Test de traduction automatique pour: {$plantName}");
        
        // Vérifier si une plante avec ce nom existe déjà
        $existing = Plant::where('common_name', $plantName)->first();
        if ($existing) {
            $this->warn("⚠️  Une plante avec ce nom existe déjà:");
            $this->line("   Nom anglais: {$existing->common_name}");
            $this->line("   Nom français: " . ($existing->french_name ?: 'Non traduit'));
            if ($existing->alternative_names) {
                $this->line("   Alternatives: " . implode(', ', $existing->alternative_names));
            }
            return Command::SUCCESS;
        }
        
        // Créer une plante temporaire pour tester
        $this->line("🌱 Création d'une plante temporaire...");
        
        $plant = Plant::create([
            'common_name' => $plantName,
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ]);
        
        $this->line("✅ Plante créée avec ID: {$plant->id}");
        
        // Simuler la traduction automatique
        $this->line("🌍 Test de traduction automatique...");
        
        // Utiliser la même logique que dans PlantController
        $translated = $this->attemptAutoTranslation($plant);
        
        // Recharger pour voir les changements
        $plant->refresh();
        
        if ($translated) {
            $this->info("✅ Traduction automatique réussie:");
            $this->line("   Nom français: {$plant->french_name}");
            if ($plant->alternative_names) {
                $this->line("   Alternatives: " . implode(', ', $plant->alternative_names));
            }
        } else {
            $this->warn("❌ Aucune traduction automatique trouvée");
            $this->line("💡 Cette plante devra être traduite manuellement");
        }
        
        // Demander si on doit garder ou supprimer la plante test
        if ($this->confirm('🗑️  Supprimer cette plante de test?', true)) {
            $plant->delete();
            $this->line("🗑️  Plante de test supprimée");
        } else {
            $this->line("💾 Plante de test conservée avec ID: {$plant->id}");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Tente une traduction automatique (copie de la méthode du PlantController)
     */
    private function attemptAutoTranslation(Plant $plant): bool
    {
        // Si déjà traduite, ne rien faire
        if ($plant->french_name) {
            return true;
        }

        // Dictionnaire de traduction rapide
        $quickTranslations = [
            // Sapins spécifiques
            'european silver fir' => ['french_name' => 'Sapin pectiné', 'alternatives' => ['Sapin blanc européen', 'Abies alba']],
            'white fir' => ['french_name' => 'Sapin du Colorado', 'alternatives' => ['Sapin blanc', 'Abies concolor']],
            'fraser fir' => ['french_name' => 'Sapin de Fraser', 'alternatives' => ['Abies fraseri']],
            'noble fir' => ['french_name' => 'Sapin noble', 'alternatives' => ['Abies procera']],
            'alpine fir' => ['french_name' => 'Sapin subalpin', 'alternatives' => ['Abies lasiocarpa']],
            
            // Érables spécifiques
            'japanese maple' => ['french_name' => 'Érable du Japon', 'alternatives' => ['Acer palmatum']],
            'amur maple' => ['french_name' => 'Érable de l\'Amour', 'alternatives' => ['Acer ginnala']],
            'paperbark maple' => ['french_name' => 'Érable à écorce de papier', 'alternatives' => ['Acer griseum']],
            'big leaf maple' => ['french_name' => 'Érable à grandes feuilles', 'alternatives' => ['Acer macrophyllum']],
            
            // Plantes d'intérieur courantes
            'monstera deliciosa' => ['french_name' => 'Monstera', 'alternatives' => ['Faux philodendron', 'Plante gruyère']],
            'snake plant' => ['french_name' => 'Sansevieria', 'alternatives' => ['Langue de belle-mère', 'Plante serpent']],
            'rubber plant' => ['french_name' => 'Caoutchouc', 'alternatives' => ['Ficus elastica', 'Hévéa']],
            'spider plant' => ['french_name' => 'Plante araignée', 'alternatives' => ['Chlorophytum', 'Phalangère']],
            'aloe vera' => ['french_name' => 'Aloès', 'alternatives' => ['Aloe vera']],
            'zz plant' => ['french_name' => 'Zamioculcas', 'alternatives' => ['Plante ZZ', 'Zamioculcas zamiifolia']],
            
            // Plantes d'extérieur
            'lavender' => ['french_name' => 'Lavande', 'alternatives' => ['Lavandula']],
            'rosemary' => ['french_name' => 'Romarin', 'alternatives' => ['Rosmarinus']],
            'basil' => ['french_name' => 'Basilic', 'alternatives' => ['Ocimum basilicum']],
            'mint' => ['french_name' => 'Menthe', 'alternatives' => ['Mentha']],
            'rose' => ['french_name' => 'Rose', 'alternatives' => ['Rosier']],
        ];

        $commonName = strtolower(trim($plant->common_name));
        
        // Rechercher une traduction exacte ou partielle
        foreach ($quickTranslations as $english => $translation) {
            if ($commonName === $english || 
                str_contains($commonName, $english) || 
                str_contains($english, $commonName)) {
                
                $plant->update([
                    'french_name' => $translation['french_name'],
                    'alternative_names' => $translation['alternatives']
                ]);
                
                return true;
            }
        }

        return false;
    }
}
