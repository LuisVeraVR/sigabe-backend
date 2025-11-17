<?php

declare(strict_types=1);

namespace App\Domain\Incidents\DTOs;

use App\Domain\Incidents\Enums\IncidentPriority;

readonly class CreateIncidentData
{
    public function __construct(
        public int $equipmentId,
        public int $reportedBy,
        public string $title,
        public string $description,
        public IncidentPriority $priority = IncidentPriority::MEDIA,
    ) {}

    public static function fromRequest(array $data, int $reportedBy): self
    {
        return new self(
            equipmentId: $data['equipment_id'],
            reportedBy: $reportedBy,
            title: $data['title'],
            description: $data['description'],
            priority: isset($data['priority'])
                ? IncidentPriority::from($data['priority'])
                : IncidentPriority::MEDIA,
        );
    }

    public function toArray(): array
    {
        return [
            'equipment_id' => $this->equipmentId,
            'reported_by' => $this->reportedBy,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
        ];
    }
}
