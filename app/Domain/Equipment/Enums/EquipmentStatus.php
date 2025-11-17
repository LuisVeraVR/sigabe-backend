<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Enums;

enum EquipmentStatus: string
{
    case AVAILABLE = 'available';
    case ON_LOAN = 'on_loan';
    case RESERVED = 'reserved';
    case MAINTENANCE = 'maintenance';
    case DAMAGED = 'damaged';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Disponible',
            self::ON_LOAN => 'En Préstamo',
            self::RESERVED => 'Reservado',
            self::MAINTENANCE => 'En Mantenimiento',
            self::DAMAGED => 'Dañado',
            self::RETIRED => 'Retirado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AVAILABLE => 'green',
            self::ON_LOAN => 'blue',
            self::RESERVED => 'yellow',
            self::MAINTENANCE => 'orange',
            self::DAMAGED => 'red',
            self::RETIRED => 'gray',
        };
    }

    public function canBeLent(): bool
    {
        return in_array($this, [self::AVAILABLE, self::RESERVED]);
    }
}
