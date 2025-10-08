<?php

namespace App\Console\Commands;

use App\Models\Plant;
use App\Services\PlantSearchService;
use Illuminate\Console\Command;

class TranslateExistingPlants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:translate {--force : Force la traduction même si déjà traduit} {--dry-run : Affiche ce qui serait fait sans modifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traduit automatiquement les noms anglais des plantes existantes en français';

    /**
     * Dictionnaire de traduction étendu (anglais -> français)
     * Plus précis pour éviter les confusions d'arrosage
     */
    private array $translationMapping = [
        // Sapins - être plus spécifique pour éviter confusion d'arrosage
        'european silver fir' => ['french_name' => 'Sapin pectiné', 'alternatives' => ['Sapin blanc européen', 'Abies alba']],
        'white fir' => ['french_name' => 'Sapin du Colorado', 'alternatives' => ['Sapin blanc', 'Abies concolor']],
        'fraser fir' => ['french_name' => 'Sapin de Fraser', 'alternatives' => ['Abies fraseri']],
        'noble fir' => ['french_name' => 'Sapin noble', 'alternatives' => ['Abies procera']],
        'alpine fir' => ['french_name' => 'Sapin subalpin', 'alternatives' => ['Abies lasiocarpa']],
        'blue spanish fir' => ['french_name' => 'Sapin d\'Espagne bleu', 'alternatives' => ['Abies pinsapo glauca']],
        'golden korean fir' => ['french_name' => 'Sapin de Corée doré', 'alternatives' => ['Abies koreana aurea']],
        'pyramidalis silver fir' => ['french_name' => 'Sapin pectiné pyramidal', 'alternatives' => ['Abies alba pyramidalis']],
        'candicans white fir' => ['french_name' => 'Sapin du Colorado blanc', 'alternatives' => ['Abies concolor candicans']],

        // Érables - être plus spécifique pour chaque variété
        'japanese maple' => ['french_name' => 'Érable du Japon', 'alternatives' => ['Acer palmatum']],
        'amur maple' => ['french_name' => 'Érable de l\'Amour', 'alternatives' => ['Acer ginnala']],
        'paperbark maple' => ['french_name' => 'Érable à écorce de papier', 'alternatives' => ['Acer griseum']],
        'fullmoon maple' => ['french_name' => 'Érable à grandes feuilles', 'alternatives' => ['Acer japonicum']],
        'big leaf maple' => ['french_name' => 'Érable à grandes feuilles', 'alternatives' => ['Acer macrophyllum']],
        'snakebark maple' => ['french_name' => 'Érable à écorce de serpent', 'alternatives' => ['Acer capillipes']],
        'flame amur maple' => ['french_name' => 'Érable de l\'Amour flamme', 'alternatives' => ['Acer ginnala flame']],
        'red rhapsody amur maple' => ['french_name' => 'Érable de l\'Amour rouge', 'alternatives' => ['Acer ginnala red rhapsody']],
        'ruby slippers amur maple' => ['french_name' => 'Érable de l\'Amour rubis', 'alternatives' => ['Acer ginnala ruby slippers']],
        'cutleaf fullmoon maple' => ['french_name' => 'Érable du Japon découpé', 'alternatives' => ['Acer japonicum aconitifolium']],
        'golden fullmoon maple' => ['french_name' => 'Érable du Japon doré', 'alternatives' => ['Acer japonicum aureum']],
        'green cascade maple' => ['french_name' => 'Érable cascade vert', 'alternatives' => ['Acer palmatum green cascade']],
        'mocha rose big leaf maple' => ['french_name' => 'Érable à grandes feuilles rose', 'alternatives' => ['Acer macrophyllum mocha rose']],

        // Érables japonais spécifiques avec noms japonais
        'johin japanese maple' => ['french_name' => 'Érable du Japon Johin', 'alternatives' => ['Acer palmatum Johin']],
        'aka shigitatsu sawa japanese maple' => ['french_name' => 'Érable du Japon Aka Shigitatsu Sawa', 'alternatives' => ['Acer palmatum Aka Shigitatsu Sawa']],
        'alpenweiss variegated dwarf japanese maple' => ['french_name' => 'Érable du Japon nain panaché', 'alternatives' => ['Acer palmatum Alpenweiss']],
        'ao shime no uchi japanese maple' => ['french_name' => 'Érable du Japon Ao Shime No Uchi', 'alternatives' => ['Acer palmatum Ao Shime No Uchi']],
        'aoyagi japanese maple' => ['french_name' => 'Érable du Japon Aoyagi', 'alternatives' => ['Acer palmatum Aoyagi']],
        'arakawa cork bark japanese maple' => ['french_name' => 'Érable du Japon à écorce liégeuse', 'alternatives' => ['Acer palmatum Arakawa']],
        'asahi zuru japanese maple' => ['french_name' => 'Érable du Japon Asahi Zuru', 'alternatives' => ['Acer palmatum Asahi Zuru']],
        'ribbon-leaf japanese maple' => ['french_name' => 'Érable du Japon à feuilles ruban', 'alternatives' => ['Acer palmatum linearilobum']],
        'purple-leaf japanese maple' => ['french_name' => 'Érable du Japon pourpre', 'alternatives' => ['Acer palmatum atropurpureum']],
        'aureum japanese maple' => ['french_name' => 'Érable du Japon doré', 'alternatives' => ['Acer palmatum aureum']],
        'azuma murasaki japanese maple' => ['french_name' => 'Érable du Japon Azuma Murasaki', 'alternatives' => ['Acer palmatum Azuma Murasaki']],
        'beni kawa coral bark japanese maple' => ['french_name' => 'Érable du Japon corail', 'alternatives' => ['Acer palmatum Beni Kawa']],
        'beni otake japanese maple' => ['french_name' => 'Érable du Japon Beni Otake', 'alternatives' => ['Acer palmatum Beni Otake']],
        'beni schichihenge japanese maple' => ['french_name' => 'Érable du Japon Beni Schichihenge', 'alternatives' => ['Acer palmatum Beni Schichihenge']],
        'beni tsukasa japanese maple' => ['french_name' => 'Érable du Japon Beni Tsukasa', 'alternatives' => ['Acer palmatum Beni Tsukasa']],
        'bloodgood japanese maple' => ['french_name' => 'Érable du Japon Bloodgood', 'alternatives' => ['Acer palmatum Bloodgood']],
        'brandt\'s dwarf japanese maple' => ['french_name' => 'Érable du Japon nain de Brandt', 'alternatives' => ['Acer palmatum Brandt\'s Dwarf']],
        'burgundy lace japanese maple' => ['french_name' => 'Érable du Japon dentelle bordeaux', 'alternatives' => ['Acer palmatum Burgundy Lace']],
        'butterfly variegated japanese maple' => ['french_name' => 'Érable du Japon papillon', 'alternatives' => ['Acer palmatum Butterfly']],
        'chantilly lace japanese maple' => ['french_name' => 'Érable du Japon dentelle Chantilly', 'alternatives' => ['Acer palmatum Chantilly Lace']],
        'chishio japanese maple' => ['french_name' => 'Érable du Japon Chishio', 'alternatives' => ['Acer palmatum Chishio']],
        'chitose yama japanese maple' => ['french_name' => 'Érable du Japon Chitose Yama', 'alternatives' => ['Acer palmatum Chitose Yama']],
        'coonara pygmy japanese maple' => ['french_name' => 'Érable du Japon pygmée', 'alternatives' => ['Acer palmatum Coonara Pygmy']],
        'crimson prince japanese maple' => ['french_name' => 'Érable du Japon prince cramoisi', 'alternatives' => ['Acer palmatum Crimson Prince']],
        'ever red lace-leaf japanese maple' => ['french_name' => 'Érable du Japon rouge persistant', 'alternatives' => ['Acer palmatum Ever Red']],

        // Noms avec "fire" qui peuvent être confondus
        'autumn fire japanese maple' => ['french_name' => 'Érable du Japon feu d\'automne', 'alternatives' => ['Acer palmatum Autumn Fire']],
        'bonfire japanese maple' => ['french_name' => 'Érable du Japon feu de joie', 'alternatives' => ['Acer palmatum Bonfire']],

        // Autres arbres spécifiques
        'flamingo boxelder' => ['french_name' => 'Érable négondo flamant', 'alternatives' => ['Acer negundo Flamingo']],
        'kelly\'s gold boxelder' => ['french_name' => 'Érable négondo doré', 'alternatives' => ['Acer negundo Kelly\'s Gold']],

        // Variétés spéciales
        'attaryi fullmoon maple' => ['french_name' => 'Érable du Japon Attaryi', 'alternatives' => ['Acer japonicum Attaryi']],
        'emmett\'s pumpkin fullmoon maple' => ['french_name' => 'Érable du Japon citrouille', 'alternatives' => ['Acer japonicum Emmett\'s Pumpkin']],

        // Plantes d'intérieur courantes
        'monstera deliciosa' => ['french_name' => 'Monstera', 'alternatives' => ['Faux philodendron', 'Plante gruyère']],
        'snake plant' => ['french_name' => 'Sansevieria', 'alternatives' => ['Langue de belle-mère', 'Plante serpent']],
        'rubber plant' => ['french_name' => 'Caoutchouc', 'alternatives' => ['Ficus elastica', 'Hévéa']],
        'spider plant' => ['french_name' => 'Plante araignée', 'alternatives' => ['Chlorophytum', 'Phalangère']],
        'aloe vera' => ['french_name' => 'Aloès', 'alternatives' => ['Aloe vera']],
        'zz plant' => ['french_name' => 'Zamioculcas', 'alternatives' => ['Plante ZZ', 'Zamioculcas zamiifolia']],
        'money tree' => ['french_name' => 'Pachira', 'alternatives' => ['Arbre à argent', 'Châtaignier de Guyane']],
        'jade plant' => ['french_name' => 'Plante de jade', 'alternatives' => ['Crassula', 'Arbre de jade']],
        'peace lily' => ['french_name' => 'Spathiphyllum', 'alternatives' => ['Lys de la paix', 'Fleur de lune']],
        'pothos' => ['french_name' => 'Pothos', 'alternatives' => ['Lierre du diable', 'Scindapsus']],
        'philodendron' => ['french_name' => 'Philodendron', 'alternatives' => ['Philodendron']],
        'fiddle leaf fig' => ['french_name' => 'Figuier lyre', 'alternatives' => ['Ficus lyrata']],
        'swiss cheese plant' => ['french_name' => 'Monstera', 'alternatives' => ['Plante gruyère', 'Faux philodendron']],
        'boston fern' => ['french_name' => 'Fougère de Boston', 'alternatives' => ['Nephrolepis']],
        'english ivy' => ['french_name' => 'Lierre anglais', 'alternatives' => ['Hedera helix']],
        'dracaena' => ['french_name' => 'Dragonnier', 'alternatives' => ['Dracaena']],
        'chinese evergreen' => ['french_name' => 'Aglaonema', 'alternatives' => ['Evergreen chinois']],
        'parlor palm' => ['french_name' => 'Palmier nain', 'alternatives' => ['Chamaedorea']],
        'prayer plant' => ['french_name' => 'Maranta', 'alternatives' => ['Plante qui prie']],
        'calathea' => ['french_name' => 'Calathéa', 'alternatives' => ['Calathea']],
        'bird of paradise' => ['french_name' => 'Oiseau de paradis', 'alternatives' => ['Strelitzia']],
        'yucca' => ['french_name' => 'Yucca', 'alternatives' => ['Yucca']],
        'weeping fig' => ['french_name' => 'Ficus benjamina', 'alternatives' => ['Figuier pleureur']],
        'rubber tree' => ['french_name' => 'Caoutchouc', 'alternatives' => ['Ficus elastica']],
        'bamboo palm' => ['french_name' => 'Palmier bambou', 'alternatives' => ['Rhapis']],
        'cast iron plant' => ['french_name' => 'Aspidistra', 'alternatives' => ['Plante de fer']],

        // Plantes d'extérieur et jardin
        'lavender' => ['french_name' => 'Lavande', 'alternatives' => ['Lavandula']],
        'rosemary' => ['french_name' => 'Romarin', 'alternatives' => ['Rosmarinus']],
        'thyme' => ['french_name' => 'Thym', 'alternatives' => ['Thymus']],
        'basil' => ['french_name' => 'Basilic', 'alternatives' => ['Ocimum basilicum']],
        'mint' => ['french_name' => 'Menthe', 'alternatives' => ['Mentha']],
        'parsley' => ['french_name' => 'Persil', 'alternatives' => ['Petroselinum']],
        'chives' => ['french_name' => 'Ciboulette', 'alternatives' => ['Allium schoenoprasum']],
        'sage' => ['french_name' => 'Sauge', 'alternatives' => ['Salvia']],
        'rose' => ['french_name' => 'Rose', 'alternatives' => ['Rosier']],
        'hydrangea' => ['french_name' => 'Hortensia', 'alternatives' => ['Hydrangea']],
        'peony' => ['french_name' => 'Pivoine', 'alternatives' => ['Paeonia']],
        'tulip' => ['french_name' => 'Tulipe', 'alternatives' => ['Tulipa']],
        'daffodil' => ['french_name' => 'Narcisse', 'alternatives' => ['Jonquille']],
        'iris' => ['french_name' => 'Iris', 'alternatives' => ['Iris']],
        'lily' => ['french_name' => 'Lys', 'alternatives' => ['Lis', 'Lilium']],
        'dahlia' => ['french_name' => 'Dahlia', 'alternatives' => ['Dahlia']],
        'sunflower' => ['french_name' => 'Tournesol', 'alternatives' => ['Helianthus']],
        'daisy' => ['french_name' => 'Marguerite', 'alternatives' => ['Bellis']],
        'pansy' => ['french_name' => 'Pensée', 'alternatives' => ['Viola']],
        'petunia' => ['french_name' => 'Pétunia', 'alternatives' => ['Petunia']],
        'impatiens' => ['french_name' => 'Impatiens', 'alternatives' => ['Balsamine']],
        'boxwood' => ['french_name' => 'Buis', 'alternatives' => ['Buxus']],
        'laurel' => ['french_name' => 'Laurier', 'alternatives' => ['Laurus']],
        'cypress' => ['french_name' => 'Cyprès', 'alternatives' => ['Cupressus']],
        'pine' => ['french_name' => 'Pin', 'alternatives' => ['Pinus']],
        'oak' => ['french_name' => 'Chêne', 'alternatives' => ['Quercus']],
        'birch' => ['french_name' => 'Bouleau', 'alternatives' => ['Betula']],

        // Succulentes et cactus
        'cactus' => ['french_name' => 'Cactus', 'alternatives' => ['Cactée']],
        'succulent' => ['french_name' => 'Succulente', 'alternatives' => ['Plante grasse']],
        'echeveria' => ['french_name' => 'Echeveria', 'alternatives' => ['Échevéria']],
        'sedum' => ['french_name' => 'Sédum', 'alternatives' => ['Orpin']],
        'haworthia' => ['french_name' => 'Haworthia', 'alternatives' => ['Haworthie']],
        'agave' => ['french_name' => 'Agave', 'alternatives' => ['Agave']],

        // Termes génériques avec plus de précision
        'fern' => ['french_name' => 'Fougère', 'alternatives' => ['Fern']],
        'palm' => ['french_name' => 'Palmier', 'alternatives' => ['Palm']],
        'ivy' => ['french_name' => 'Lierre', 'alternatives' => ['Ivy']],
        'geranium' => ['french_name' => 'Géranium', 'alternatives' => ['Pelargonium']],
        'begonia' => ['french_name' => 'Bégonia', 'alternatives' => ['Begonia']],
        'african violet' => ['french_name' => 'Violette africaine', 'alternatives' => ['Saintpaulia']],
        'orchid' => ['french_name' => 'Orchidée', 'alternatives' => ['Orchidaceae']],
        'bamboo' => ['french_name' => 'Bambou', 'alternatives' => ['Bambusa']],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('🌍 Début de la traduction des plantes existantes...');

        if ($dryRun) {
            $this->warn('🧪 MODE DRY-RUN : Aucune modification ne sera apportée à la base de données');
        }

        // Récupérer toutes les plantes
        $query = Plant::query();
        
        if (!$force) {
            // Ne traiter que les plantes sans nom français
            $query->whereNull('french_name');
        }

        $plants = $query->get();
        
        $this->info("📊 {$plants->count()} plante(s) à traiter");
        $this->newLine();

        $translated = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($plants as $plant) {
            $commonName = strtolower(trim($plant->common_name));
            
            // Rechercher dans le dictionnaire
            $translation = null;
            foreach ($this->translationMapping as $english => $frenchData) {
                // Recherche exacte ou contient
                if ($commonName === $english || 
                    str_contains($commonName, $english) || 
                    str_contains($english, $commonName)) {
                    $translation = $frenchData;
                    break;
                }
            }

            if ($translation) {
                if (!$dryRun) {
                    $plant->update([
                        'french_name' => $translation['french_name'],
                        'alternative_names' => $translation['alternatives']
                    ]);
                }
                
                $this->line("✅ {$plant->common_name} → {$translation['french_name']}");
                if (!empty($translation['alternatives'])) {
                    $this->line("   📝 Alternatives: " . implode(', ', $translation['alternatives']));
                }
                $translated++;
            } else {
                $this->line("❓ {$plant->common_name} - Pas de traduction trouvée");
                $notFound++;
            }
        }

        $this->newLine();
        $this->info("🎉 Traduction terminée !");
        $this->info("📊 Résultats:");
        $this->info("   • {$translated} plantes traduites");
        $this->info("   • {$notFound} plantes sans traduction");

        if ($notFound > 0) {
            $this->newLine();
            $this->warn("💡 Conseil: Vous pouvez ajouter manuellement les traductions manquantes");
            $this->warn("   avec l'endpoint PUT /plants/{id}/french-names");
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("🚀 Pour appliquer les changements, relancez sans --dry-run");
        }

        return Command::SUCCESS;
    }
}
