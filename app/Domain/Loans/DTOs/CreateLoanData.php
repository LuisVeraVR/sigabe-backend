<?php

declare(strict_types=1);

namespace App\Domain\Loans\DTOs;

class CreateLoanData
{
    public function __construct(
        public readonly int $userId,
        public readonly int $equipmentId,
        public readonly string $expectedReturnDate,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            equipmentId: $data['equipment_id'],
            expectedReturnDate: $data['expected_return_date'],
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'equipment_id' => $this->equipmentId,
            'expected_return_date' => $this->expectedReturnDate,
            'notes' => $this->notes,
        ];
    }
}
