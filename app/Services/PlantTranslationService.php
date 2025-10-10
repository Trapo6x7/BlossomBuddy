<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Facades\Log;

class PlantTranslationService
{
    private TranslationApiService $apiService;

    public function __construct(TranslationApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Dictionnaire de traduction étendu (version complète)
     */
    private array $translationMapping = [
        // Sapins spécifiques
        'european silver fir' => ['french_name' => 'Sapin pectiné', 'alternatives' => ['Sapin blanc européen', 'Abies alba']],
        'white fir' => ['french_name' => 'Sapin du Colorado', 'alternatives' => ['Sapin blanc', 'Abies concolor']],
        'fraser fir' => ['french_name' => 'Sapin de Fraser', 'alternatives' => ['Abies fraseri']],
        'noble fir' => ['french_name' => 'Sapin noble', 'alternatives' => ['Abies procera']],
        'alpine fir' => ['french_name' => 'Sapin subalpin', 'alternatives' => ['Abies lasiocarpa']],
        'grand fir' => ['french_name' => 'Sapin géant', 'alternatives' => ['Abies grandis']],
        'balsam fir' => ['french_name' => 'Sapin baumier', 'alternatives' => ['Abies balsamea']],
        
        // Érables spécifiques pour éviter confusion d'arrosage
        'japanese maple' => ['french_name' => 'Érable du Japon', 'alternatives' => ['Acer palmatum']],
        'amur maple' => ['french_name' => 'Érable de l\'Amour', 'alternatives' => ['Acer ginnala']],
        'paperbark maple' => ['french_name' => 'Érable à écorce de papier', 'alternatives' => ['Acer griseum']],
        'big leaf maple' => ['french_name' => 'Érable à grandes feuilles', 'alternatives' => ['Acer macrophyllum']],
        'red maple' => ['french_name' => 'Érable rouge', 'alternatives' => ['Acer rubrum']],
        'sugar maple' => ['french_name' => 'Érable à sucre', 'alternatives' => ['Acer saccharum']],
        'silver maple' => ['french_name' => 'Érable argenté', 'alternatives' => ['Acer saccharinum']],
        'norway maple' => ['french_name' => 'Érable de Norvège', 'alternatives' => ['Acer platanoides']],
        
        // Chênes spécifiques
        'white oak' => ['french_name' => 'Chêne blanc américain', 'alternatives' => ['Quercus alba']],
        'red oak' => ['french_name' => 'Chêne rouge', 'alternatives' => ['Quercus rubra']],
        'english oak' => ['french_name' => 'Chêne pédonculé', 'alternatives' => ['Quercus robur']],
        'live oak' => ['french_name' => 'Chêne vert', 'alternatives' => ['Quercus virginiana']],
        'pin oak' => ['french_name' => 'Chêne des marais', 'alternatives' => ['Quercus palustris']],
        
        // Pins spécifiques
        'eastern white pine' => ['french_name' => 'Pin blanc du Canada', 'alternatives' => ['Pinus strobus']],
        'scots pine' => ['french_name' => 'Pin sylvestre', 'alternatives' => ['Pinus sylvestris']],
        'ponderosa pine' => ['french_name' => 'Pin ponderosa', 'alternatives' => ['Pinus ponderosa']],
        'lodgepole pine' => ['french_name' => 'Pin tordu', 'alternatives' => ['Pinus contorta']],
        'douglas fir' => ['french_name' => 'Sapin de Douglas', 'alternatives' => ['Pseudotsuga menziesii']],
        
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
        'fiddle leaf fig' => ['french_name' => 'Ficus lyrata', 'alternatives' => ['Figuier lyre']],
        'dracaena' => ['french_name' => 'Dragonnier', 'alternatives' => ['Dracaena']],
        'boston fern' => ['french_name' => 'Fougère de Boston', 'alternatives' => ['Nephrolepis exaltata']],
        'bird of paradise' => ['french_name' => 'Oiseau du paradis', 'alternatives' => ['Strelitzia']],
        'chinese evergreen' => ['french_name' => 'Aglaonema', 'alternatives' => ['Aglaonema']],
        'calathea' => ['french_name' => 'Calathea', 'alternatives' => ['Maranta']],
        'prayer plant' => ['french_name' => 'Maranta', 'alternatives' => ['Plante qui prie']],
        'dieffenbachia' => ['french_name' => 'Dieffenbachia', 'alternatives' => ['Canne des muets']],
        'english ivy' => ['french_name' => 'Lierre anglais', 'alternatives' => ['Hedera helix']],
        'monstera adansonii' => ['french_name' => 'Monstera à trous', 'alternatives' => ['Philodendron troué']],
        'bamboo palm' => ['french_name' => 'Palmier bambou', 'alternatives' => ['Rhapis']],
        'cast iron plant' => ['french_name' => 'Aspidistra', 'alternatives' => ['Plante de fer']],
        
        // Plantes d'extérieur et jardin
        'lavender' => ['french_name' => 'Lavande', 'alternatives' => ['Lavandula']],
        'rosemary' => ['french_name' => 'Romarin', 'alternatives' => ['Rosmarinus']],
        'thyme' => ['french_name' => 'Thym', 'alternatives' => ['Thymus']],
        'basil' => ['french_name' => 'Basilic', 'alternatives' => ['Ocimum basilicum']],
        'mint' => ['french_name' => 'Menthe', 'alternatives' => ['Mentha']],
        'sage' => ['french_name' => 'Sauge', 'alternatives' => ['Salvia']],
        'oregano' => ['french_name' => 'Origan', 'alternatives' => ['Origanum']],
        'parsley' => ['french_name' => 'Persil', 'alternatives' => ['Petroselinum']],
        'cilantro' => ['french_name' => 'Coriandre', 'alternatives' => ['Coriandrum sativum']],
        'dill' => ['french_name' => 'Aneth', 'alternatives' => ['Anethum graveolens']],
        
        // Roses et rosiers
        'rose' => ['french_name' => 'Rose', 'alternatives' => ['Rosier']],
        'hybrid tea rose' => ['french_name' => 'Rose thé hybride', 'alternatives' => ['Rosier à grandes fleurs']],
        'climbing rose' => ['french_name' => 'Rosier grimpant', 'alternatives' => ['Rose grimpante']],
        'shrub rose' => ['french_name' => 'Rosier arbustif', 'alternatives' => ['Rose arbuste']],
        
        // Légumes communs
        'tomato' => ['french_name' => 'Tomate', 'alternatives' => ['Solanum lycopersicum']],
        'lettuce' => ['french_name' => 'Laitue', 'alternatives' => ['Lactuca sativa']],
        'carrot' => ['french_name' => 'Carotte', 'alternatives' => ['Daucus carota']],
        'onion' => ['french_name' => 'Oignon', 'alternatives' => ['Allium cepa']],
        'garlic' => ['french_name' => 'Ail', 'alternatives' => ['Allium sativum']],
        'potato' => ['french_name' => 'Pomme de terre', 'alternatives' => ['Solanum tuberosum']],
        'pepper' => ['french_name' => 'Poivron', 'alternatives' => ['Capsicum']],
        'cucumber' => ['french_name' => 'Concombre', 'alternatives' => ['Cucumis sativus']],
        'zucchini' => ['french_name' => 'Courgette', 'alternatives' => ['Cucurbita pepo']],
        'spinach' => ['french_name' => 'Épinard', 'alternatives' => ['Spinacia oleracea']],
        
        // Fleurs communes
        'sunflower' => ['french_name' => 'Tournesol', 'alternatives' => ['Helianthus']],
        'tulip' => ['french_name' => 'Tulipe', 'alternatives' => ['Tulipa']],
        'daffodil' => ['french_name' => 'Jonquille', 'alternatives' => ['Narcisse']],
        'iris' => ['french_name' => 'Iris', 'alternatives' => ['Iris']],
        'peony' => ['french_name' => 'Pivoine', 'alternatives' => ['Paeonia']],
        'dahlia' => ['french_name' => 'Dahlia', 'alternatives' => ['Dahlia']],
        'marigold' => ['french_name' => 'Souci', 'alternatives' => ['Calendula', 'Tagetes']],
        'zinnia' => ['french_name' => 'Zinnia', 'alternatives' => ['Zinnia']],
        'cosmos' => ['french_name' => 'Cosmos', 'alternatives' => ['Cosmos']],
        'pansy' => ['french_name' => 'Pensée', 'alternatives' => ['Viola']],
        'petunia' => ['french_name' => 'Pétunia', 'alternatives' => ['Petunia']],
        'impatiens' => ['french_name' => 'Impatiens', 'alternatives' => ['Balsamine']],
        
        // Arbustes et buissons
        'boxwood' => ['french_name' => 'Buis', 'alternatives' => ['Buxus']],
        'laurel' => ['french_name' => 'Laurier', 'alternatives' => ['Laurus']],
        'yew' => ['french_name' => 'If', 'alternatives' => ['Taxus']],
        'cypress' => ['french_name' => 'Cyprès', 'alternatives' => ['Cupressus']],
        'juniper' => ['french_name' => 'Genévrier', 'alternatives' => ['Juniperus']],
        'holly' => ['french_name' => 'Houx', 'alternatives' => ['Ilex']],
        'rhododendron' => ['french_name' => 'Rhododendron', 'alternatives' => ['Rhododendron']],
        'azalea' => ['french_name' => 'Azalée', 'alternatives' => ['Azalea']],
        'hydrangea' => ['french_name' => 'Hortensia', 'alternatives' => ['Hydrangea']],
        'forsythia' => ['french_name' => 'Forsythia', 'alternatives' => ['Mimosa de Paris']],
        'lilac' => ['french_name' => 'Lilas', 'alternatives' => ['Syringa']],
        'viburnum' => ['french_name' => 'Viorne', 'alternatives' => ['Viburnum']],
        
        // Plantes grimpantes
        'ivy' => ['french_name' => 'Lierre', 'alternatives' => ['Hedera']],
        'clematis' => ['french_name' => 'Clématite', 'alternatives' => ['Clematis']],
        'wisteria' => ['french_name' => 'Glycine', 'alternatives' => ['Wisteria']],
        'honeysuckle' => ['french_name' => 'Chèvrefeuille', 'alternatives' => ['Lonicera']],
        'grape vine' => ['french_name' => 'Vigne', 'alternatives' => ['Vitis']],
        
        // Cactus et succulentes
        'cactus' => ['french_name' => 'Cactus', 'alternatives' => ['Cactée']],
        'succulent' => ['french_name' => 'Plante grasse', 'alternatives' => ['Succulente']],
        'barrel cactus' => ['french_name' => 'Cactus tonneau', 'alternatives' => ['Echinocactus']],
        'prickly pear' => ['french_name' => 'Figuier de Barbarie', 'alternatives' => ['Opuntia']],
        'christmas cactus' => ['french_name' => 'Cactus de Noël', 'alternatives' => ['Schlumbergera']],
        'easter cactus' => ['french_name' => 'Cactus de Pâques', 'alternatives' => ['Rhipsalidopsis']],
        'echeveria' => ['french_name' => 'Echeveria', 'alternatives' => ['Échévéria']],
        'sedum' => ['french_name' => 'Orpin', 'alternatives' => ['Sedum']],
        'hens and chicks' => ['french_name' => 'Poule et poussins', 'alternatives' => ['Sempervivum']],
        
        // Fougères
        'fern' => ['french_name' => 'Fougère', 'alternatives' => ['Fougère']],
        'maidenhair fern' => ['french_name' => 'Capillaire', 'alternatives' => ['Adiantum']],
        'staghorn fern' => ['french_name' => 'Corne d\'élan', 'alternatives' => ['Platycerium']],
        
        // Orchidées
        'orchid' => ['french_name' => 'Orchidée', 'alternatives' => ['Orchidaceae']],
        'phalaenopsis' => ['french_name' => 'Phalaenopsis', 'alternatives' => ['Orchidée papillon']],
        'cattleya' => ['french_name' => 'Cattleya', 'alternatives' => ['Cattleya']],
        'dendrobium' => ['french_name' => 'Dendrobium', 'alternatives' => ['Dendrobium']],
        
        // Autres plantes diverses
        'bamboo' => ['french_name' => 'Bambou', 'alternatives' => ['Bambusa']],
        'moss' => ['french_name' => 'Mousse', 'alternatives' => ['Mousse']],
        'grass' => ['french_name' => 'Herbe', 'alternatives' => ['Graminée']],
        'wheat' => ['french_name' => 'Blé', 'alternatives' => ['Triticum']],
        'corn' => ['french_name' => 'Maïs', 'alternatives' => ['Zea mays']],
        'rice' => ['french_name' => 'Riz', 'alternatives' => ['Oryza sativa']],
        'barley' => ['french_name' => 'Orge', 'alternatives' => ['Hordeum']],
        'oat' => ['french_name' => 'Avoine', 'alternatives' => ['Avena']],
        
        // Plantes aquatiques
        'water lily' => ['french_name' => 'Nénuphar', 'alternatives' => ['Nymphaea']],
        'lotus' => ['french_name' => 'Lotus', 'alternatives' => ['Nelumbo']],
        'water hyacinth' => ['french_name' => 'Jacinthe d\'eau', 'alternatives' => ['Eichhornia']],
        
        // Arbres fruitiers
        'apple tree' => ['french_name' => 'Pommier', 'alternatives' => ['Malus domestica']],
        'pear tree' => ['french_name' => 'Poirier', 'alternatives' => ['Pyrus']],
        'cherry tree' => ['french_name' => 'Cerisier', 'alternatives' => ['Prunus']],
        'plum tree' => ['french_name' => 'Prunier', 'alternatives' => ['Prunus domestica']],
        'peach tree' => ['french_name' => 'Pêcher', 'alternatives' => ['Prunus persica']],
        'apricot tree' => ['french_name' => 'Abricotier', 'alternatives' => ['Prunus armeniaca']],
        'orange tree' => ['french_name' => 'Oranger', 'alternatives' => ['Citrus sinensis']],
        'lemon tree' => ['french_name' => 'Citronnier', 'alternatives' => ['Citrus limon']],
        'lime tree' => ['french_name' => 'Tilleul', 'alternatives' => ['Tilia']],
        'grapefruit tree' => ['french_name' => 'Pamplemousse', 'alternatives' => ['Citrus paradisi']],
        'fig tree' => ['french_name' => 'Figuier', 'alternatives' => ['Ficus carica']],
        'olive tree' => ['french_name' => 'Olivier', 'alternatives' => ['Olea europaea']],
        'walnut tree' => ['french_name' => 'Noyer', 'alternatives' => ['Juglans']],
        'chestnut tree' => ['french_name' => 'Châtaignier', 'alternatives' => ['Castanea']],
        'almond tree' => ['french_name' => 'Amandier', 'alternatives' => ['Prunus dulcis']],
        
        // Fleurs sauvages et vivaces
        'wildflower' => ['french_name' => 'Fleur sauvage', 'alternatives' => ['Fleur des champs']],
        'daisy' => ['french_name' => 'Marguerite', 'alternatives' => ['Bellis']],
        'black-eyed susan' => ['french_name' => 'Rudbeckie', 'alternatives' => ['Rudbeckia']],
        'coneflower' => ['french_name' => 'Échinacée', 'alternatives' => ['Echinacea']],
        'bee balm' => ['french_name' => 'Monarde', 'alternatives' => ['Monarda']],
        'cardinal flower' => ['french_name' => 'Lobélie cardinale', 'alternatives' => ['Lobelia cardinalis']],
        'goldenrod' => ['french_name' => 'Verge d\'or', 'alternatives' => ['Solidago']],
        'aster' => ['french_name' => 'Aster', 'alternatives' => ['Symphyotrichum']],
        'lupine' => ['french_name' => 'Lupin', 'alternatives' => ['Lupinus']],
        'poppy' => ['french_name' => 'Coquelicot', 'alternatives' => ['Papaver']],
        'cornflower' => ['french_name' => 'Bleuet', 'alternatives' => ['Centaurea']],
        'forget-me-not' => ['french_name' => 'Myosotis', 'alternatives' => ['Myosotis']],
        'morning glory' => ['french_name' => 'Belle-de-jour', 'alternatives' => ['Ipomoea']],
        'four o\'clock' => ['french_name' => 'Belle-de-nuit', 'alternatives' => ['Mirabilis']],
        'foxglove' => ['french_name' => 'Digitale', 'alternatives' => ['Digitalis']],
        'hollyhock' => ['french_name' => 'Rose trémière', 'alternatives' => ['Alcea']],
        'delphinium' => ['french_name' => 'Pied d\'alouette', 'alternatives' => ['Delphinium']],
        'larkspur' => ['french_name' => 'Pied d\'alouette', 'alternatives' => ['Consolida']],
        'sweet pea' => ['french_name' => 'Pois de senteur', 'alternatives' => ['Lathyrus']],
        'nasturtium' => ['french_name' => 'Capucine', 'alternatives' => ['Tropaeolum']],
        'bachelor button' => ['french_name' => 'Bleuet', 'alternatives' => ['Centaurea cyanus']],
        'calendula' => ['french_name' => 'Souci', 'alternatives' => ['Calendula officinalis']],
        'snapdragon' => ['french_name' => 'Muflier', 'alternatives' => ['Antirrhinum']],
        'sweet alyssum' => ['french_name' => 'Alysse', 'alternatives' => ['Lobularia']],
        'candytuft' => ['french_name' => 'Ibéris', 'alternatives' => ['Iberis']],
        'stock' => ['french_name' => 'Giroflée', 'alternatives' => ['Matthiola']],
        'wallflower' => ['french_name' => 'Giroflée ravenelle', 'alternatives' => ['Erysimum']],
        'sweet william' => ['french_name' => 'Œillet de poète', 'alternatives' => ['Dianthus barbatus']],
        'carnation' => ['french_name' => 'Œillet', 'alternatives' => ['Dianthus']],
        'pink' => ['french_name' => 'Œillet mignardise', 'alternatives' => ['Dianthus plumarius']],
        'baby\'s breath' => ['french_name' => 'Gypsophile', 'alternatives' => ['Gypsophila']],
        'statice' => ['french_name' => 'Statice', 'alternatives' => ['Limonium']],
        'globe amaranth' => ['french_name' => 'Amarante globe', 'alternatives' => ['Gomphrena']],
        'celosia' => ['french_name' => 'Célosie', 'alternatives' => ['Celosia']],
        'cockscomb' => ['french_name' => 'Crête de coq', 'alternatives' => ['Celosia cristata']],
        'amaranth' => ['french_name' => 'Amarante', 'alternatives' => ['Amaranthus']],
        'love-lies-bleeding' => ['french_name' => 'Amarante caudée', 'alternatives' => ['Amaranthus caudatus']],
        'joseph\'s coat' => ['french_name' => 'Amarante tricolore', 'alternatives' => ['Amaranthus tricolor']],
        'four o\'clock flower' => ['french_name' => 'Belle-de-nuit', 'alternatives' => ['Mirabilis jalapa']],
        'moonflower' => ['french_name' => 'Belle-de-nuit', 'alternatives' => ['Ipomoea alba']],
        'evening primrose' => ['french_name' => 'Onagre', 'alternatives' => ['Oenothera']],
        'night-blooming cereus' => ['french_name' => 'Reine de la nuit', 'alternatives' => ['Epiphyllum oxypetalum']],
        'angel\'s trumpet' => ['french_name' => 'Trompette des anges', 'alternatives' => ['Brugmansia']],
        'devil\'s trumpet' => ['french_name' => 'Trompette du diable', 'alternatives' => ['Datura']],
        'jimsonweed' => ['french_name' => 'Stramoine', 'alternatives' => ['Datura stramonium']],
        'castor bean' => ['french_name' => 'Ricin', 'alternatives' => ['Ricinus communis']],
        
        // Autres plantes communes du dictionnaire de traduction
        'begonia' => ['french_name' => 'Bégonia', 'alternatives' => ['Begonia']],
        'african violet' => ['french_name' => 'Violette africaine', 'alternatives' => ['Saintpaulia']],
    ];

    /**
     * Traduit automatiquement une plante si une correspondance est trouvée
     */
    public function translatePlant(Plant $plant): bool
    {
        // Si déjà traduite, ne rien faire
        if ($plant->french_name) {
            return true;
        }

        // 1. Essayer d'abord le dictionnaire local (plus rapide et précis)
        if ($this->translateFromDictionary($plant)) {
            return true;
        }

        // 2. Fallback sur l'API de traduction gratuite
        if ($this->translateUsingApi($plant)) {
            return true;
        }

        // Si aucune traduction trouvée
        Log::info("No translation found (dictionary + API) for plant: {$plant->common_name}");
        return false;
    }

    /**
     * Traduction via le dictionnaire local
     */
    private function translateFromDictionary(Plant $plant): bool
    {
        $commonName = strtolower(trim($plant->common_name));
        
        // Rechercher une traduction exacte ou partielle
        foreach ($this->translationMapping as $english => $translation) {
            if ($this->matchesPattern($commonName, $english)) {
                $plant->update([
                    'french_name' => $translation['french_name'],
                    'alternative_names' => $translation['alternatives']
                ]);
                
                Log::info("Dictionary translation: {$plant->common_name} → {$translation['french_name']}");
                return true;
            }
        }

        return false;
    }

    /**
     * Traduction via API externe
     */
    private function translateUsingApi(Plant $plant): bool
    {
        try {
            $apiTranslation = $this->apiService->translatePlantName($plant->common_name);
            
            if ($apiTranslation) {
                $plant->update([
                    'french_name' => $apiTranslation['french_name'],
                    'alternative_names' => $apiTranslation['alternatives']
                ]);
                
                Log::info("API translation: {$plant->common_name} → {$apiTranslation['french_name']}");
                return true;
            }
        } catch (\Exception $e) {
            Log::error("API translation failed for {$plant->common_name}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Vérifie si un nom de plante correspond à un motif de traduction
     */
    private function matchesPattern(string $commonName, string $english): bool
    {
        // Correspondance exacte
        if ($commonName === $english) {
            return true;
        }
        
        // Le nom commun contient le mot anglais
        if (str_contains($commonName, $english)) {
            return true;
        }
        
        // Le mot anglais contient le nom commun (pour les noms courts)
        if (str_contains($english, $commonName) && strlen($commonName) > 3) {
            return true;
        }
        
        // Correspondance de mots individuels pour des noms composés
        $commonWords = explode(' ', $commonName);
        $englishWords = explode(' ', $english);
        
        // Si au moins 70% des mots correspondent
        $matches = 0;
        foreach ($englishWords as $englishWord) {
            if (strlen($englishWord) > 2) { // Ignorer les mots trop courts
                foreach ($commonWords as $commonWord) {
                    if (strlen($commonWord) > 2 && 
                        (str_contains($commonWord, $englishWord) || str_contains($englishWord, $commonWord))) {
                        $matches++;
                        break;
                    }
                }
            }
        }
        
        $matchPercentage = count($englishWords) > 0 ? $matches / count($englishWords) : 0;
        return $matchPercentage >= 0.7;
    }

    /**
     * Traduit toutes les plantes non traduites d'une collection
     */
    public function translateMultiplePlants($plants): int
    {
        $translated = 0;
        
        foreach ($plants as $plant) {
            if ($this->translatePlant($plant)) {
                $translated++;
            }
        }
        
        return $translated;
    }

    /**
     * Obtient le nombre de traductions disponibles dans le dictionnaire
     */
    public function getAvailableTranslationsCount(): int
    {
        return count($this->translationMapping);
    }

    /**
     * Obtient toutes les traductions disponibles (pour debug)
     */
    public function getAllTranslations(): array
    {
        return $this->translationMapping;
    }
}