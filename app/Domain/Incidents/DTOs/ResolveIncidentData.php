<?php

declare(strict_types=1);

namespace App\Domain\Incidents\DTOs;

readonly class ResolveIncidentData
{
    public function __construct(
        public string $resolutionNotes,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            resolutionNotes: $data['resolution_notes'],
        );
    }

    public function toArray(): array
    {
        return [
            'resolution_notes' => $this->resolutionNotes,
        ];
    }
}
