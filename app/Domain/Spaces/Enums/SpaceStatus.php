<?php

declare(strict_types=1);

namespace App\Domain\Spaces\Enums;

enum SpaceStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case MAINTENANCE = 'maintenance';
    case RESERVED = 'reserved';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
            self::UNAVAILABLE => 'No Disponible',
            self::MAINTENANCE => 'En Mantenimiento',
            self::RESERVED => 'Reservado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::UNAVAILABLE => 'error',
            self::MAINTENANCE => 'warning',
            self::RESERVED => 'info',
        };
    }
}
