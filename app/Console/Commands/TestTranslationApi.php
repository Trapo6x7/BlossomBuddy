<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TranslationApiService;
use App\Models\Plant;

class TestTranslationApi extends Command
{
    protected $signature = 'translation:test {text? : Texte à traduire} {--plant-test : Tester avec des plantes réelles}';
    protected $description = 'Teste l\'API de traduction gratuite MyMemory';

    public function handle(TranslationApiService $translationService)
    {
        $this->info('🌐 Test de l\'API de traduction MyMemory');
        $this->newLine();

        // Test de connectivité
        $this->info('🔌 Test de connectivité...');
        if (!$translationService->testApiConnection()) {
            $this->error('❌ L\'API de traduction n\'est pas accessible');
            return Command::FAILURE;
        }
        $this->info('✅ API accessible');
        $this->newLine();

        if ($this->option('plant-test')) {
            return $this->testWithPlants($translationService);
        }

        $text = $this->argument('text') ?? $this->ask('Entrez le texte à traduire en anglais');
        
        if (!$text) {
            $this->error('Aucun texte fourni');
            return Command::FAILURE;
        }

        $this->info("🔤 Traduction de: '{$text}'");
        
        // Test traduction simple
        $simpleTranslation = $translationService->translateToFrench($text);
        $this->line("📝 Traduction simple: " . ($simpleTranslation ?? 'ÉCHEC'));

        // Test traduction de plante
        $plantTranslation = $translationService->translatePlantName($text);
        
        if ($plantTranslation) {
            $this->info("🌿 Traduction de plante:");
            $this->line("   Nom français: {$plantTranslation['french_name']}");
            $this->line("   Alternatives: " . implode(', ', $plantTranslation['alternatives']));
        } else {
            $this->warn("❌ Échec de la traduction de plante");
        }

        return Command::SUCCESS;
    }

    private function testWithPlants(TranslationApiService $translationService)
    {
        $this->info('🌱 Test avec de vraies plantes non traduites...');
        
        // Récupérer quelques plantes non traduites
        $untranslatedPlants = Plant::whereNull('french_name')->limit(3)->get();
        
        if ($untranslatedPlants->isEmpty()) {
            $this->warn('Aucune plante non traduite trouvée');
            return Command::SUCCESS;
        }

        foreach ($untranslatedPlants as $plant) {
            $this->newLine();
            $this->info("🧪 Test: {$plant->common_name}");
            
            $translation = $translationService->translatePlantName($plant->common_name);
            
            if ($translation) {
                $this->line("✅ Traduction réussie:");
                $this->line("   Français: {$translation['french_name']}");
                $this->line("   Alternatives: " . implode(', ', $translation['alternatives']));
                
                if ($this->confirm("Appliquer cette traduction à la plante ?")) {
                    $plant->update([
                        'french_name' => $translation['french_name'],
                        'alternative_names' => $translation['alternatives']
                    ]);
                    $this->info("💾 Traduction sauvegardée");
                }
            } else {
                $this->error("❌ Échec de la traduction");
            }
        }

        return Command::SUCCESS;
    }
}
