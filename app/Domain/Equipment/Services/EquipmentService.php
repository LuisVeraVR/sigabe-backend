<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Services;

use App\Domain\Equipment\DTOs\CreateEquipmentData;
use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Repositories\EquipmentRepository;
use App\Domain\Shared\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EquipmentService
{
    public function __construct(
        private readonly EquipmentRepository $repository
    ) {}

    /**
     * Listar equipos con filtros
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * Obtener equipo por ID
     */
    public function findById(int $id): ?Equipment
    {
        return $this->repository->findById($id);
    }

    /**
     * Crear nuevo equipo
     */
    public function create(CreateEquipmentData $data): Equipment
    {
        $equipment = $this->repository->create($data->toArray());

        AuditLog::log(
            action: 'created',
            module: 'equipment',
            description: "Equipo creado: {$equipment->name} ({$equipment->asset_code})",
            record: $equipment
        );

        return $equipment;
    }

    /**
     * Actualizar equipo
     */
    public function update(Equipment $equipment, array $data): Equipment
    {
        $originalData = $equipment->toArray();

        $this->repository->update($equipment, $data);

        $equipment->refresh();

        AuditLog::log(
            action: 'updated',
            module: 'equipment',
            description: "Equipo actualizado: {$equipment->name}",
            record: $equipment,
            changes: array_diff_assoc($equipment->toArray(), $originalData)
        );

        return $equipment;
    }

    /**
     * Eliminar equipo
     */
    public function delete(Equipment $equipment): bool
    {
        $name = $equipment->name;
        $assetCode = $equipment->asset_code;

        $deleted = $this->repository->delete($equipment);

        if ($deleted) {
            AuditLog::log(
                action: 'deleted',
                module: 'equipment',
                description: "Equipo eliminado: {$name} ({$assetCode})"
            );
        }

        return $deleted;
    }

    /**
     * Cambiar estado del equipo
     */
    public function changeStatus(Equipment $equipment, EquipmentStatus $newStatus, ?string $reason = null): Equipment
    {
        $equipment->changeStatus($newStatus, $reason);

        return $equipment->refresh();
    }

    /**
     * Verificar disponibilidad para préstamo
     */
    public function checkAvailability(int $equipmentId): array
    {
        $equipment = $this->repository->findById($equipmentId);

        if (!$equipment) {
            return [
                'available' => false,
                'reason' => 'Equipo no encontrado',
            ];
        }

        if (!$equipment->canBeLent()) {
            return [
                'available' => false,
                'reason' => "Estado actual: {$equipment->status->label()}",
                'equipment' => $equipment,
            ];
        }

        return [
            'available' => true,
            'equipment' => $equipment,
            'suggested_duration_hours' => $equipment->type->average_loan_duration_hours,
        ];
    }

    /**
     * Obtener equipos disponibles para préstamo
     */
    public function getAvailableForLoan(array $filters = []): Collection
    {
        return $this->repository->getAvailableForLoan($filters);
    }

    /**
     * Obtener estadísticas
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Obtener historial de un equipo
     */
    public function getHistory(int $equipmentId): array
    {
        $equipment = $this->repository->findById($equipmentId);

        if (!$equipment) {
            return [];
        }

        return [
            'maintenances' => $equipment->maintenances()
                ->with('performedBy')
                ->orderBy('scheduled_date', 'desc')
                ->get(),
            'loans' => $equipment->loans()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            'incidents' => $equipment->incidents()
                ->with('reportedBy')
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }
}
