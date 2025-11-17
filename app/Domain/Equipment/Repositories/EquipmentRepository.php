<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Repositories;

use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EquipmentRepository
{
    /**
     * Listar equipos con filtros y paginación
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Equipment::with(['type', 'brand']);

        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtro por tipo
        if (!empty($filters['type_id'])) {
            $query->where('equipment_type_id', $filters['type_id']);
        }

        // Filtro por marca
        if (!empty($filters['brand_id'])) {
            $query->where('equipment_brand_id', $filters['brand_id']);
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtro por condición
        if (!empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        // Filtro por disponibilidad
        if (!empty($filters['available'])) {
            $query->available();
        }

        // Filtro por garantía vigente
        if (!empty($filters['under_warranty'])) {
            $query->underWarranty();
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Buscar equipo por ID con relaciones
     */
public function findById(int $id): ?Equipment
{
    return Equipment::query()
        ->with([
            'equipmentType',
            'equipmentBrand',
        ])
        ->find($id);
}

    /**
     * Buscar equipo por código de activo
     */
    public function findByAssetCode(string $assetCode): ?Equipment
    {
        return Equipment::where('asset_code', $assetCode)->first();
    }

    /**
     * Buscar equipo por número de serie
     */
    public function findBySerialNumber(string $serialNumber): ?Equipment
    {
        return Equipment::where('serial_number', $serialNumber)->first();
    }

    /**
     * Crear nuevo equipo
     */
    public function create(array $data): Equipment
    {
        // Generar asset_code si no viene
        if (empty($data['asset_code'])) {
            $data['asset_code'] = Equipment::generateAssetCode();
        }

        return Equipment::create($data);
    }

    /**
     * Actualizar equipo
     */
    public function update(Equipment $equipment, array $data): bool
    {
        return $equipment->update($data);
    }

    /**
     * Eliminar equipo (soft delete)
     */
    public function delete(Equipment $equipment): bool
    {
        return $equipment->delete();
    }

    /**
     * Obtener equipos disponibles para préstamo
     */
    public function getAvailableForLoan(array $filters = []): Collection
    {
        $query = Equipment::with(['type', 'brand'])
            ->available();

        if (!empty($filters['type_id'])) {
            $query->where('equipment_type_id', $filters['type_id']);
        }

        return $query->get();
    }

    /**
     * Obtener equipos que requieren mantenimiento próximo
     */
    public function getRequiringMaintenance(int $days = 7): Collection
    {
        return Equipment::with(['type', 'maintenances'])
            ->whereHas('maintenances', function ($q) use ($days) {
                $q->scheduled()
                    ->whereBetween('scheduled_date', [now(), now()->addDays($days)]);
            })
            ->get();
    }

    /**
     * Obtener estadísticas de equipos
     */
    public function getStatistics(): array
    {
        return [
            'total' => Equipment::count(),
            'available' => Equipment::where('status', EquipmentStatus::AVAILABLE)->count(),
            'on_loan' => Equipment::where('status', EquipmentStatus::ON_LOAN)->count(),
            'maintenance' => Equipment::where('status', EquipmentStatus::MAINTENANCE)->count(),
            'damaged' => Equipment::where('status', EquipmentStatus::DAMAGED)->count(),
            'by_type' => Equipment::selectRaw('equipment_type_id, count(*) as count')
                ->groupBy('equipment_type_id')
                ->with('type:id,name')
                ->get()
                ->pluck('count', 'type.name'),
            'by_condition' => Equipment::selectRaw('condition, count(*) as count')
                ->groupBy('condition')
                ->get()
                ->pluck('count', 'condition'),
        ];
    }
}
