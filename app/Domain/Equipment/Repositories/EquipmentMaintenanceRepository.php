<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Repositories;

use App\Domain\Equipment\Enums\MaintenanceStatus;
use App\Domain\Equipment\Models\EquipmentMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EquipmentMaintenanceRepository
{
    /**
     * Listar mantenimientos con filtros y paginación
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EquipmentMaintenance::with(['equipment.type', 'performedBy']);

        // Filtro por equipo
        if (!empty($filters['equipment_id'])) {
            $query->where('equipment_id', $filters['equipment_id']);
        }

        // Filtro por tipo de mantenimiento
        if (!empty($filters['maintenance_type'])) {
            $query->where('maintenance_type', $filters['maintenance_type']);
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtro por prioridad
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Filtro por técnico
        if (!empty($filters['technician_id'])) {
            $query->where('performed_by_user_id', $filters['technician_id']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['from_date'])) {
            $query->whereDate('scheduled_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('scheduled_date', '<=', $filters['to_date']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'scheduled_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Buscar mantenimiento por ID
     */
    public function findById(int $id): ?EquipmentMaintenance
    {
        return EquipmentMaintenance::with([
            'equipment.type',
            'equipment.brand',
            'performedBy',
        ])->find($id);
    }

    /**
     * Crear nuevo mantenimiento
     */
    public function create(array $data): EquipmentMaintenance
    {
        return EquipmentMaintenance::create($data);
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(EquipmentMaintenance $maintenance, array $data): bool
    {
        return $maintenance->update($data);
    }

    /**
     * Eliminar mantenimiento
     */
    public function delete(EquipmentMaintenance $maintenance): bool
    {
        return $maintenance->delete();
    }

    /**
     * Obtener mantenimientos próximos
     */
    public function getUpcoming(int $days = 7): Collection
    {
        return EquipmentMaintenance::with(['equipment.type', 'performedBy'])
            ->upcoming($days)
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    /**
     * Obtener mantenimientos vencidos
     */
    public function getOverdue(): Collection
    {
        return EquipmentMaintenance::with(['equipment.type', 'performedBy'])
            ->overdue()
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    /**
     * Obtener mantenimientos pendientes de un equipo
     */
    public function getPendingByEquipment(int $equipmentId): Collection
    {
        return EquipmentMaintenance::where('equipment_id', $equipmentId)
            ->pending()
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    /**
     * Obtener historial de mantenimientos de un equipo
     */
    public function getHistoryByEquipment(int $equipmentId): Collection
    {
        return EquipmentMaintenance::where('equipment_id', $equipmentId)
            ->with('performedBy')
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas de mantenimientos
     */
    public function getStatistics(): array
    {
        return [
            'total' => EquipmentMaintenance::count(),
            'scheduled' => EquipmentMaintenance::where('status', MaintenanceStatus::SCHEDULED)->count(),
            'in_progress' => EquipmentMaintenance::where('status', MaintenanceStatus::IN_PROGRESS)->count(),
            'completed' => EquipmentMaintenance::where('status', MaintenanceStatus::COMPLETED)->count(),
            'overdue' => EquipmentMaintenance::overdue()->count(),
            'upcoming_7_days' => EquipmentMaintenance::upcoming(7)->count(),
            'by_type' => EquipmentMaintenance::selectRaw('maintenance_type, count(*) as count')
                ->groupBy('maintenance_type')
                ->get()
                ->pluck('count', 'maintenance_type'),
            'total_cost' => EquipmentMaintenance::completed()
                ->sum('cost'),
        ];
    }
}
