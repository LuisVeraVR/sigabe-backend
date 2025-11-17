<?php

declare(strict_types=1);

namespace App\Domain\Loans\DTOs;

class ApproveLoanData
{
    public function __construct(
        public readonly int $loanId,
        public readonly int $approvedBy,
    ) {}

    public static function fromRequest(int $loanId, int $approvedBy): self
    {
        return new self(
            loanId: $loanId,
            approvedBy: $approvedBy,
        );
    }
}
