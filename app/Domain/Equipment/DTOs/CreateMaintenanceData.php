<?php

declare(strict_types=1);

namespace App\Domain\Equipment\DTOs;

use App\Domain\Equipment\Enums\MaintenancePriority;
use App\Domain\Equipment\Enums\MaintenanceStatus;
use App\Domain\Equipment\Enums\MaintenanceType;

class CreateMaintenanceData
{
    public function __construct(
        public readonly int $equipmentId,
        public readonly ?int $performedByUserId,
        public readonly MaintenanceType $maintenanceType,
        public readonly string $title,
        public readonly string $description,
        public readonly string $scheduledDate,
        public readonly MaintenanceStatus $status,
        public readonly MaintenancePriority $priority,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            equipmentId: $data['equipment_id'],
            performedByUserId: $data['performed_by_user_id'] ?? auth()->id(),
            maintenanceType: MaintenanceType::from($data['maintenance_type']),
            title: $data['title'],
            description: $data['description'],
            scheduledDate: $data['scheduled_date'],
            status: MaintenanceStatus::from($data['status'] ?? 'scheduled'),
            priority: MaintenancePriority::from($data['priority'] ?? 'medium'),
        );
    }

    public function toArray(): array
    {
        return [
            'equipment_id' => $this->equipmentId,
            'performed_by_user_id' => $this->performedByUserId,
            'maintenance_type' => $this->maintenanceType,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_date' => $this->scheduledDate,
            'status' => $this->status,
            'priority' => $this->priority,
        ];
    }
}
