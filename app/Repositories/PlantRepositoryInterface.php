<?php

namespace App\Repositories;

use App\Models\Plant;
use Illuminate\Support\Collection;

interface PlantRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Plant;
    public function create(array $data): Plant;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;

    // Ajouté pour la recherche avancée
    public function findByFrenchName(string $name): ?Plant;
    public function findByCommonNameLike(string $name): ?Plant;
    public function searchByNameOrAlternative(string $query, int $limit = 10): Collection;
        /**
     * Stocke une plante en base
     * @param array $plant
     * @return void
     */
    public function store(array $plant): void;
}
