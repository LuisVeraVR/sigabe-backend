<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Services;

use App\Domain\Equipment\DTOs\CreateMaintenanceData;
use App\Domain\Equipment\Models\EquipmentMaintenance;
use App\Domain\Equipment\Repositories\EquipmentMaintenanceRepository;
use App\Domain\Shared\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EquipmentMaintenanceService
{
    public function __construct(
        private readonly EquipmentMaintenanceRepository $repository
    ) {}

    /**
     * Listar mantenimientos con filtros
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * Obtener mantenimiento por ID
     */
    public function findById(int $id): ?EquipmentMaintenance
    {
        return $this->repository->findById($id);
    }

    /**
     * Crear nuevo mantenimiento
     */
    public function create(CreateMaintenanceData $data): EquipmentMaintenance
    {
        $maintenance = $this->repository->create($data->toArray());

        AuditLog::log(
            action: 'created',
            module: 'equipment_maintenance',
            description: "Mantenimiento programado: {$maintenance->title}",
            record: $maintenance
        );

        return $maintenance;
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(EquipmentMaintenance $maintenance, array $data): EquipmentMaintenance
    {
        $this->repository->update($maintenance, $data);

        $maintenance->refresh();

        AuditLog::log(
            action: 'updated',
            module: 'equipment_maintenance',
            description: "Mantenimiento actualizado: {$maintenance->title}",
            record: $maintenance
        );

        return $maintenance;
    }

    /**
     * Iniciar mantenimiento
     */
    public function start(EquipmentMaintenance $maintenance): EquipmentMaintenance
    {
        $maintenance->start();

        AuditLog::log(
            action: 'started',
            module: 'equipment_maintenance',
            description: "Mantenimiento iniciado: {$maintenance->title}",
            record: $maintenance
        );

        return $maintenance->refresh();
    }

    /**
     * Completar mantenimiento
     */
    public function complete(EquipmentMaintenance $maintenance, string $actionsTaken, ?float $cost = null): EquipmentMaintenance
    {
        $maintenance->complete($actionsTaken, $cost);

        AuditLog::log(
            action: 'completed',
            module: 'equipment_maintenance',
            description: "Mantenimiento completado: {$maintenance->title}",
            record: $maintenance,
            changes: [
                'actions_taken' => $actionsTaken,
                'cost' => $cost,
            ]
        );

        return $maintenance->refresh();
    }

    /**
     * Cancelar mantenimiento
     */
    public function cancel(EquipmentMaintenance $maintenance, string $reason): EquipmentMaintenance
    {
        $maintenance->cancel($reason);

        AuditLog::log(
            action: 'cancelled',
            module: 'equipment_maintenance',
            description: "Mantenimiento cancelado: {$maintenance->title}",
            record: $maintenance,
            changes: ['reason' => $reason]
        );

        return $maintenance->refresh();
    }

    /**
     * Obtener mantenimientos próximos
     */
    public function getUpcoming(int $days = 7): Collection
    {
        return $this->repository->getUpcoming($days);
    }

    /**
     * Obtener mantenimientos vencidos
     */
    public function getOverdue(): Collection
    {
        return $this->repository->getOverdue();
    }

    /**
     * Obtener estadísticas
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
