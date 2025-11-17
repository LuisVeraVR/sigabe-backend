<?php

declare(strict_types=1);

namespace App\Domain\Reservations\DTOs;

readonly class ApproveReservationData
{
    public function __construct(
        public int $approvedBy,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data, int $approvedBy): self
    {
        return new self(
            approvedBy: $approvedBy,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'approved_by' => $this->approvedBy,
            'notes' => $this->notes,
        ];
    }
}
