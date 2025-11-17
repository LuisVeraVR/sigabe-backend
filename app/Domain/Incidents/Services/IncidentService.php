<?php

declare(strict_types=1);

namespace App\Domain\Incidents\Services;

use App\Domain\Incidents\DTOs\AssignIncidentData;
use App\Domain\Incidents\DTOs\CreateIncidentData;
use App\Domain\Incidents\DTOs\ResolveIncidentData;
use App\Domain\Incidents\Enums\IncidentStatus;
use App\Domain\Incidents\Models\Incident;
use App\Domain\Incidents\Repositories\IncidentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function __construct(
        private readonly IncidentRepository $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findById(int $id): ?Incident
    {
        return $this->repository->findById($id);
    }

    public function create(CreateIncidentData $data): Incident
    {
        return DB::transaction(function () use ($data) {
            $incident = $this->repository->create([
                ...$data->toArray(),
                'status' => IncidentStatus::REPORTADO,
            ]);

            return $incident->load(['equipment', 'reportedBy']);
        });
    }

    public function assign(int $id, AssignIncidentData $data): Incident
    {
        return DB::transaction(function () use ($id, $data) {
            $incident = $this->repository->findById($id);

            if (!$incident) {
                throw new \Exception('Incidente no encontrado');
            }

            if (!$incident->canBeAssigned()) {
                throw new \Exception('Este incidente no puede ser asignado en su estado actual');
            }

            $this->repository->update($incident, [
                'assigned_to' => $data->assignedTo,
                'status' => IncidentStatus::EN_REVISION,
            ]);

            return $incident->fresh()->load(['equipment', 'reportedBy', 'assignedTo']);
        });
    }

    public function unassign(int $id): Incident
    {
        return DB::transaction(function () use ($id) {
            $incident = $this->repository->findById($id);

            if (!$incident) {
                throw new \Exception('Incidente no encontrado');
            }

            if (!$incident->isAssigned()) {
                throw new \Exception('Este incidente no está asignado');
            }

            $this->repository->update($incident, [
                'assigned_to' => null,
                'status' => IncidentStatus::REPORTADO,
            ]);

            return $incident->fresh()->load(['equipment', 'reportedBy']);
        });
    }

    public function startRepair(int $id): Incident
    {
        return DB::transaction(function () use ($id) {
            $incident = $this->repository->findById($id);

            if (!$incident) {
                throw new \Exception('Incidente no encontrado');
            }

            if ($incident->status !== IncidentStatus::EN_REVISION) {
                throw new \Exception('Solo se pueden reparar incidentes en revisión');
            }

            if (!$incident->isAssigned()) {
                throw new \Exception('El incidente debe estar asignado para iniciar reparación');
            }

            $this->repository->update($incident, [
                'status' => IncidentStatus::EN_REPARACION,
            ]);

            return $incident->fresh()->load(['equipment', 'reportedBy', 'assignedTo']);
        });
    }

    public function resolve(int $id, ResolveIncidentData $data): Incident
    {
        return DB::transaction(function () use ($id, $data) {
            $incident = $this->repository->findById($id);

            if (!$incident) {
                throw new \Exception('Incidente no encontrado');
            }

            if (!$incident->canBeResolved()) {
                throw new \Exception('Este incidente no puede ser resuelto en su estado actual');
            }

            $this->repository->update($incident, [
                'status' => IncidentStatus::RESUELTO,
                'resolution_notes' => $data->resolutionNotes,
                'resolved_at' => now(),
            ]);

            return $incident->fresh()->load(['equipment', 'reportedBy', 'assignedTo']);
        });
    }

    public function close(int $id): Incident
    {
        return DB::transaction(function () use ($id) {
            $incident = $this->repository->findById($id);

            if (!$incident) {
                throw new \Exception('Incidente no encontrado');
            }

            if (!$incident->canBeClosed()) {
                throw new \Exception('Solo se pueden cerrar incidentes resueltos');
            }

            $this->repository->update($incident, [
                'status' => IncidentStatus::CERRADO,
                'closed_at' => now(),
            ]);

            return $incident->fresh()->load(['equipment', 'reportedBy', 'assignedTo']);
        });
    }

    public function reopen(int $id): Incident
    {
        return DB::transaction(function () use ($id) {
            $incident = $this->repository->findById($id);

            if (!$incident) {
                throw new \Exception('Incidente no encontrado');
            }

            if (!$incident->canBeReopened()) {
                throw new \Exception('Solo se pueden reabrir incidentes cerrados');
            }

            $status = $incident->isAssigned()
                ? IncidentStatus::EN_REVISION
                : IncidentStatus::REPORTADO;

            $this->repository->update($incident, [
                'status' => $status,
                'closed_at' => null,
                'resolved_at' => null,
                'resolution_notes' => null,
            ]);

            return $incident->fresh()->load(['equipment', 'reportedBy', 'assignedTo']);
        });
    }

    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    public function getByReporter(int $userId): Collection
    {
        return $this->repository->getByReporter($userId);
    }

    public function getByAssignee(int $userId): Collection
    {
        return $this->repository->getByAssignee($userId);
    }

    public function getUnassigned(): Collection
    {
        return $this->repository->getUnassigned();
    }

    public function getByEquipment(int $equipmentId): Collection
    {
        return $this->repository->getByEquipment($equipmentId);
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function delete(int $id): bool
    {
        $incident = $this->repository->findById($id);

        if (!$incident) {
            throw new \Exception('Incidente no encontrado');
        }

        return $this->repository->delete($incident);
    }
}
