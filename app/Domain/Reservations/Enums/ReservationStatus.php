<?php

declare(strict_types=1);

namespace App\Domain\Reservations\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobada',
            self::ACTIVE => 'Activa',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada',
            self::REJECTED => 'Rechazada',
            self::EXPIRED => 'Expirada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::ACTIVE => 'success',
            self::COMPLETED => 'secondary',
            self::CANCELLED => 'default',
            self::REJECTED => 'danger',
            self::EXPIRED => 'danger',
        };
    }
}
