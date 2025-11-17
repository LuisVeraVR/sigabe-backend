<?php

declare(strict_types=1);

namespace App\Domain\Spaces\Repositories;

use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;
use App\Domain\Spaces\Models\Space;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SpaceRepository
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Space::query();

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['space_type'])) {
            $query->where('space_type', $filters['space_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['building'])) {
            $query->byBuilding($filters['building']);
        }

        if (isset($filters['floor'])) {
            $query->byFloor($filters['floor']);
        }

        if (isset($filters['min_capacity'])) {
            $query->withCapacity((int) $filters['min_capacity']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Space
    {
        return Space::find($id);
    }

    public function findByCode(string $code): ?Space
    {
        return Space::where('code', $code)->first();
    }

    public function create(array $data): Space
    {
        return Space::create($data);
    }

    public function update(Space $space, array $data): Space
    {
        $space->update($data);
        return $space->fresh();
    }

    public function delete(Space $space): bool
    {
        return $space->delete();
    }

    public function getAvailable(): Collection
    {
        return Space::available()->get();
    }

    public function getByType(SpaceType $type): Collection
    {
        return Space::byType($type)->get();
    }

    public function getByStatus(SpaceStatus $status): Collection
    {
        return Space::where('status', $status)->get();
    }

    public function getByBuilding(string $building): Collection
    {
        return Space::byBuilding($building)->get();
    }

    public function getAllBuildings(): array
    {
        return Space::whereNotNull('building')
            ->distinct()
            ->pluck('building')
            ->toArray();
    }

    public function getAllFloors(): array
    {
        return Space::whereNotNull('floor')
            ->distinct()
            ->pluck('floor')
            ->toArray();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Space::count(),
            'by_status' => [
                'available' => Space::available()->count(),
                'unavailable' => Space::unavailable()->count(),
                'maintenance' => Space::inMaintenance()->count(),
                'reserved' => Space::reserved()->count(),
            ],
            'by_type' => [
                'classroom' => Space::byType(SpaceType::CLASSROOM)->count(),
                'lab' => Space::byType(SpaceType::LAB)->count(),
                'auditorium' => Space::byType(SpaceType::AUDITORIUM)->count(),
                'meeting_room' => Space::byType(SpaceType::MEETING_ROOM)->count(),
                'library' => Space::byType(SpaceType::LIBRARY)->count(),
                'storage' => Space::byType(SpaceType::STORAGE)->count(),
                'other' => Space::byType(SpaceType::OTHER)->count(),
            ],
            'total_capacity' => Space::sum('capacity'),
            'buildings' => $this->getAllBuildings(),
        ];
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = Space::where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
