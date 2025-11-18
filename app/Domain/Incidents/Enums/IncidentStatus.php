<?php

declare(strict_types=1);

namespace App\Domain\Incidents\Enums;

enum IncidentStatus: string
{
    case REPORTADO = 'reportado';
    case EN_REVISION = 'en_revision';
    case EN_REPARACION = 'en_reparacion';
    case RESUELTO = 'resuelto';
    case CERRADO = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::REPORTADO => 'Reportado',
            self::EN_REVISION => 'En Revisión',
            self::EN_REPARACION => 'En Reparación',
            self::RESUELTO => 'Resuelto',
            self::CERRADO => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REPORTADO => 'warning',
            self::EN_REVISION => 'info',
            self::EN_REPARACION => 'primary',
            self::RESUELTO => 'success',
            self::CERRADO => 'secondary',
        };
    }
}
