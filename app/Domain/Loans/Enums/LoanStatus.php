<?php

declare(strict_types=1);

namespace App\Domain\Loans\Enums;

enum LoanStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case RETURNED = 'returned';
    case REJECTED = 'rejected';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobado',
            self::RETURNED => 'Devuelto',
            self::REJECTED => 'Rechazado',
            self::OVERDUE => 'Vencido',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::RETURNED => 'blue',
            self::REJECTED => 'red',
            self::OVERDUE => 'red',
        };
    }
}
