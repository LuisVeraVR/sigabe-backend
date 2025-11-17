<?php

declare(strict_types=1);

namespace App\Domain\Incidents\Repositories;

use App\Domain\Incidents\Enums\IncidentPriority;
use App\Domain\Incidents\Enums\IncidentStatus;
use App\Domain\Incidents\Models\Incident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IncidentRepository
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Incident::with(['equipment', 'reportedBy', 'assignedTo'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($q) => $q->where('priority', $filters['priority']))
            ->when(isset($filters['equipment_id']), fn ($q) => $q->where('equipment_id', $filters['equipment_id']))
            ->when(isset($filters['reported_by']), fn ($q) => $q->where('reported_by', $filters['reported_by']))
            ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
            ->when(isset($filters['unassigned']) && $filters['unassigned'], fn ($q) => $q->whereNull('assigned_to'))
            ->orderByPriority('desc')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Incident
    {
        return Incident::with(['equipment', 'reportedBy', 'assignedTo'])
            ->find($id);
    }

    public function create(array $data): Incident
    {
        return Incident::create($data);
    }

    public function update(Incident $incident, array $data): bool
    {
        return $incident->update($data);
    }

    public function delete(Incident $incident): bool
    {
        return $incident->delete();
    }

    public function getActive(): Collection
    {
        return Incident::with(['equipment', 'reportedBy', 'assignedTo'])
            ->active()
            ->orderByPriority('desc')
            ->latest()
            ->get();
    }

    public function getByReporter(int $userId): Collection
    {
        return Incident::with(['equipment', 'assignedTo'])
            ->byReporter($userId)
            ->orderByPriority('desc')
            ->latest()
            ->get();
    }

    public function getByAssignee(int $userId): Collection
    {
        return Incident::with(['equipment', 'reportedBy'])
            ->byAssignee($userId)
            ->orderByPriority('desc')
            ->latest()
            ->get();
    }

    public function getUnassigned(): Collection
    {
        return Incident::with(['equipment', 'reportedBy'])
            ->unassigned()
            ->active()
            ->orderByPriority('desc')
            ->latest()
            ->get();
    }

    public function getByEquipment(int $equipmentId): Collection
    {
        return Incident::with(['reportedBy', 'assignedTo'])
            ->byEquipment($equipmentId)
            ->orderByPriority('desc')
            ->latest()
            ->get();
    }

    public function getByStatus(IncidentStatus $status): Collection
    {
        return Incident::with(['equipment', 'reportedBy', 'assignedTo'])
            ->where('status', $status)
            ->orderByPriority('desc')
            ->latest()
            ->get();
    }

    public function getByPriority(IncidentPriority $priority): Collection
    {
        return Incident::with(['equipment', 'reportedBy', 'assignedTo'])
            ->byPriority($priority)
            ->active()
            ->latest()
            ->get();
    }

    public function countActive(): int
    {
        return Incident::active()->count();
    }

    public function countByStatus(IncidentStatus $status): int
    {
        return Incident::where('status', $status)->count();
    }

    public function countByPriority(IncidentPriority $priority): int
    {
        return Incident::byPriority($priority)->active()->count();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Incident::count(),
            'active' => Incident::active()->count(),
            'reportado' => Incident::reportado()->count(),
            'en_revision' => Incident::enRevision()->count(),
            'en_reparacion' => Incident::enReparacion()->count(),
            'resuelto' => Incident::resuelto()->count(),
            'cerrado' => Incident::cerrado()->count(),
            'unassigned' => Incident::unassigned()->active()->count(),
            'by_priority' => [
                'critica' => Incident::byPriority(IncidentPriority::CRITICA)->active()->count(),
                'alta' => Incident::byPriority(IncidentPriority::ALTA)->active()->count(),
                'media' => Incident::byPriority(IncidentPriority::MEDIA)->active()->count(),
                'baja' => Incident::byPriority(IncidentPriority::BAJA)->active()->count(),
            ],
        ];
    }
}
