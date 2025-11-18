<?php

declare(strict_types=1);

namespace App\Domain\Reservations\DTOs;

readonly class CreateReservationData
{
    public function __construct(
        public int $userId,
        public int $equipmentId,
        public string $startDate,
        public string $endDate,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            equipmentId: $data['equipment_id'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'equipment_id' => $this->equipmentId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'notes' => $this->notes,
        ];
    }
}
