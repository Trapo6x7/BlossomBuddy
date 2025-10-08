<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlantSearchService
{
    /**
     * Dictionnaire de correspondance français -> anglais pour les plantes courantes
     */
    private array $frenchToEnglishMapping = [
        // Plantes d'intérieur courantes
        'monstera' => 'Monstera deliciosa',
        'ficus' => 'Ficus',
        'pothos' => 'Pothos',
        'philodendron' => 'Philodendron',
        'sansevieria' => 'Snake Plant',
        'langue de belle-mère' => 'Snake Plant',
        'plante serpent' => 'Snake Plant',
        'cactus' => 'Cactus',
        'succulente' => 'Succulent',
        'aloe vera' => 'Aloe Vera',
        'aloès' => 'Aloe Vera',
        'caoutchouc' => 'Rubber Plant',
        'ficus elastica' => 'Rubber Plant',
        'plante araignée' => 'Spider Plant',
        'chlorophytum' => 'Spider Plant',
        'dracaena' => 'Dracaena',
        'dragonnier' => 'Dracaena',
        'palmier' => 'Palm',
        'fougère' => 'Fern',
        'lierre' => 'Ivy',
        'géranium' => 'Geranium',
        'bégonia' => 'Begonia',
        'violette africaine' => 'African Violet',
        'orchidée' => 'Orchid',
        'bambou' => 'Bamboo',
        'yucca' => 'Yucca',
        'zamioculcas' => 'ZZ Plant',
        'plante zz' => 'ZZ Plant',
        'pachira' => 'Money Tree',
        'arbre à argent' => 'Money Tree',
        'jade' => 'Jade Plant',
        'crassula' => 'Jade Plant',
        
        // Plantes d'extérieur et jardin
        'rose' => 'Rose',
        'rosier' => 'Rose',
        'lavande' => 'Lavender',
        'romarin' => 'Rosemary',
        'thym' => 'Thyme',
        'basilic' => 'Basil',
        'menthe' => 'Mint',
        'persil' => 'Parsley',
        'ciboulette' => 'Chives',
        'sauge' => 'Sage',
        'hortensia' => 'Hydrangea',
        'pivoine' => 'Peony',
        'tulipe' => 'Tulip',
        'narcisse' => 'Daffodil',
        'jonquille' => 'Daffodil',
        'iris' => 'Iris',
        'lys' => 'Lily',
        'lis' => 'Lily',
        'dahlia' => 'Dahlia',
        'tournesol' => 'Sunflower',
        'marguerite' => 'Daisy',
        'pensée' => 'Pansy',
        'pétunia' => 'Petunia',
        'impatiens' => 'Impatiens',
        'buis' => 'Boxwood',
        'laurier' => 'Laurel',
        'if' => 'Yew',
        'cyprès' => 'Cypress',
        'sapin' => 'Fir',
        'pin' => 'Pine',
        'chêne' => 'Oak',
        'érable' => 'Maple',
        'bouleau' => 'Birch',
    ];

    /**
     * Trouve une plante spécifique par son nom (français ou anglais)
     * Retourne la première plante trouvée ou null
     */
    public function findPlantByName(string $name): ?Plant
    {
        $results = $this->searchPlants($name, 1);
        return $results->first();
    }

    /**
     * Recherche intelligente de plantes par nom français ou anglais
     */
    public function searchPlants(string $query, int $limit = 10): Collection
    {
        $query = trim(strtolower($query));
        
        if (empty($query)) {
            return collect();
        }

        // 1. Recherche exacte par nom français
        $exactFrenchMatch = Plant::whereRaw('LOWER(french_name) = ?', [$query])->first();
        if ($exactFrenchMatch) {
            return collect([$exactFrenchMatch]);
        }

        // 2. Recherche dans le dictionnaire de correspondances
        $englishEquivalent = $this->frenchToEnglishMapping[$query] ?? null;
        if ($englishEquivalent) {
            $mappingMatch = Plant::whereRaw('LOWER(common_name) LIKE ?', ['%' . strtolower($englishEquivalent) . '%'])->first();
            if ($mappingMatch) {
                return collect([$mappingMatch]);
            }
        }

        // 3. Recherche floue dans tous les champs
        return Plant::where(function ($queryBuilder) use ($query) {
            $queryBuilder
                ->whereRaw('LOWER(common_name) LIKE ?', ['%' . $query . '%'])
                ->orWhereRaw('LOWER(french_name) LIKE ?', ['%' . $query . '%'])
                ->orWhereRaw('JSON_SEARCH(LOWER(alternative_names), "one", ?) IS NOT NULL', ['%' . $query . '%']);
        })
        ->limit($limit)
        ->get();
    }

    /**
     * Obtient des suggestions d'autocomplétion
     */
    public function getAutocompleteSuggestions(string $query, int $limit = 5): array
    {
        $query = trim(strtolower($query));
        
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Suggestions depuis la base de données
        $dbSuggestions = Plant::where(function ($queryBuilder) use ($query) {
            $queryBuilder
                ->whereRaw('LOWER(common_name) LIKE ?', [$query . '%'])
                ->orWhereRaw('LOWER(french_name) LIKE ?', [$query . '%']);
        })
        ->limit($limit)
        ->get()
        ->map(function ($plant) {
            return [
                'id' => $plant->id,
                'text' => $plant->french_name ?: $plant->common_name,
                'secondary' => $plant->french_name ? $plant->common_name : null,
                'source' => 'database'
            ];
        })
        ->toArray();

        $suggestions = array_merge($suggestions, $dbSuggestions);

        // Suggestions depuis le dictionnaire
        $dictionarySuggestions = [];
        foreach ($this->frenchToEnglishMapping as $french => $english) {
            if (str_starts_with(strtolower($french), $query)) {
                $dictionarySuggestions[] = [
                    'text' => $french,
                    'secondary' => $english,
                    'source' => 'dictionary'
                ];
            }
        }

        $suggestions = array_merge($suggestions, array_slice($dictionarySuggestions, 0, $limit - count($suggestions)));

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Trouve ou crée une plante basée sur un nom français
     */
    public function findOrSuggestPlant(string $frenchName): array
    {
        $results = $this->searchPlants($frenchName, 1);
        
        if ($results->isNotEmpty()) {
            return [
                'found' => true,
                'plant' => $results->first(),
                'confidence' => 'high'
            ];
        }

        // Si pas trouvé, suggérer des alternatives
        $suggestions = $this->getAutocompleteSuggestions($frenchName, 3);
        
        return [
            'found' => false,
            'suggestions' => $suggestions,
            'message' => "Plante non trouvée. Voici quelques suggestions similaires :"
        ];
    }

    /**
     * Met à jour les noms français pour une plante existante
     */
    public function updatePlantFrenchNames(int $plantId, string $frenchName, array $alternativeNames = []): bool
    {
        $plant = Plant::find($plantId);
        
        if (!$plant) {
            return false;
        }

        $plant->update([
            'french_name' => $frenchName,
            'alternative_names' => $alternativeNames
        ]);

        return true;
    }

    /**
     * Enrichit le dictionnaire avec de nouvelles correspondances
     */
    public function addToMapping(string $frenchName, string $englishName): void
    {
        $this->frenchToEnglishMapping[strtolower($frenchName)] = $englishName;
        // Dans une vraie application, ceci pourrait être persisté en base de données
    }
}