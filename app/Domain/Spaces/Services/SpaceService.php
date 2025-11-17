<?php

declare(strict_types=1);

namespace App\Domain\Spaces\Services;

use App\Domain\Spaces\DTOs\CreateSpaceData;
use App\Domain\Spaces\DTOs\UpdateSpaceData;
use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;
use App\Domain\Spaces\Models\Space;
use App\Domain\Spaces\Repositories\SpaceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SpaceService
{
    public function __construct(
        private readonly SpaceRepository $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findById(int $id): ?Space
    {
        return $this->repository->findById($id);
    }

    public function create(CreateSpaceData $data): Space
    {
        return DB::transaction(function () use ($data) {
            // Validar que el código no exista
            if ($this->repository->codeExists($data->code)) {
                throw new \Exception("El código '{$data->code}' ya existe");
            }

            return $this->repository->create($data->toArray());
        });
    }

    public function update(int $id, UpdateSpaceData $data): Space
    {
        return DB::transaction(function () use ($id, $data) {
            $space = $this->repository->findById($id);

            if (!$space) {
                throw new \Exception('Espacio no encontrado');
            }

            // Si se está actualizando el código, verificar que no exista
            if ($data->code && $this->repository->codeExists($data->code, $id)) {
                throw new \Exception("El código '{$data->code}' ya existe");
            }

            // Si el espacio está reservado, no permitir ciertos cambios
            if ($space->isReserved() && $data->status && $data->status !== SpaceStatus::RESERVED) {
                throw new \Exception('No se puede cambiar el estado de un espacio reservado directamente');
            }

            return $this->repository->update($space, $data->toArray());
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $space = $this->repository->findById($id);

            if (!$space) {
                throw new \Exception('Espacio no encontrado');
            }

            if ($space->isReserved()) {
                throw new \Exception('No se puede eliminar un espacio que está reservado');
            }

            return $this->repository->delete($space);
        });
    }

    public function markAsAvailable(int $id): Space
    {
        return DB::transaction(function () use ($id) {
            $space = $this->repository->findById($id);

            if (!$space) {
                throw new \Exception('Espacio no encontrado');
            }

            return $this->repository->update($space, [
                'status' => SpaceStatus::AVAILABLE,
            ]);
        });
    }

    public function markAsUnavailable(int $id): Space
    {
        return DB::transaction(function () use ($id) {
            $space = $this->repository->findById($id);

            if (!$space) {
                throw new \Exception('Espacio no encontrado');
            }

            if ($space->isReserved()) {
                throw new \Exception('No se puede marcar como no disponible un espacio reservado');
            }

            return $this->repository->update($space, [
                'status' => SpaceStatus::UNAVAILABLE,
            ]);
        });
    }

    public function markAsInMaintenance(int $id): Space
    {
        return DB::transaction(function () use ($id) {
            $space = $this->repository->findById($id);

            if (!$space) {
                throw new \Exception('Espacio no encontrado');
            }

            if ($space->isReserved()) {
                throw new \Exception('No se puede marcar en mantenimiento un espacio reservado');
            }

            return $this->repository->update($space, [
                'status' => SpaceStatus::MAINTENANCE,
            ]);
        });
    }

    public function getAvailable(): Collection
    {
        return $this->repository->getAvailable();
    }

    public function getByType(SpaceType $type): Collection
    {
        return $this->repository->getByType($type);
    }

    public function getByStatus(SpaceStatus $status): Collection
    {
        return $this->repository->getByStatus($status);
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function getBuildings(): array
    {
        return $this->repository->getAllBuildings();
    }

    public function getFloors(): array
    {
        return $this->repository->getAllFloors();
    }
}
