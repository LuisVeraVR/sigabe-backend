<?php

declare(strict_types=1);

namespace App\Domain\Incidents\DTOs;

readonly class AssignIncidentData
{
    public function __construct(
        public int $assignedTo,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            assignedTo: $data['assigned_to'],
        );
    }

    public function toArray(): array
    {
        return [
            'assigned_to' => $this->assignedTo,
        ];
    }
}
