<?php

namespace App\Repositories;

use App\Models\Plant;
use App\Repositories\PlantRepositoryInterface;
use Illuminate\Support\Collection;

class PlantRepository implements PlantRepositoryInterface
{
    public function all(): Collection
    {
        return Plant::all();
    }

    public function find(int $id): ?Plant
    {
        return Plant::find($id);
    }

    public function create(array $data): Plant
    {
        return Plant::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = Plant::find($id);
        if (!$user) {
            return false;
        }
        return $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = Plant::find($id);
        if (!$user) {
            return false;
        }
        return $user->delete();
    }

    public function findByFrenchName(string $name): ?Plant
    {
        return Plant::whereRaw('LOWER(french_name) = ?', [strtolower($name)])->first();
    }

    public function findByCommonNameLike(string $name): ?Plant
    {
        return Plant::whereRaw('LOWER(common_name) LIKE ?', ['%' . strtolower($name) . '%'])->first();
    }

    public function searchByNameOrAlternative(string $query, int $limit = 10): Collection
    {
        return Plant::where(function ($queryBuilder) use ($query) {
            $queryBuilder
                ->whereRaw('LOWER(common_name) LIKE ?', ['%' . $query . '%'])
                ->orWhereRaw('LOWER(french_name) LIKE ?', ['%' . $query . '%'])
                ->orWhereRaw('JSON_SEARCH(LOWER(alternative_names), "one", ?) IS NOT NULL', ['%' . $query . '%']);
        })
            ->limit($limit)
            ->get();
    }
}