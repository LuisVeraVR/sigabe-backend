<?php

declare(strict_types=1);

namespace App\Domain\Incidents\Enums;

enum IncidentPriority: string
{
    case BAJA = 'baja';
    case MEDIA = 'media';
    case ALTA = 'alta';
    case CRITICA = 'critica';

    public function label(): string
    {
        return match ($this) {
            self::BAJA => 'Baja',
            self::MEDIA => 'Media',
            self::ALTA => 'Alta',
            self::CRITICA => 'Crítica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BAJA => 'secondary',
            self::MEDIA => 'info',
            self::ALTA => 'warning',
            self::CRITICA => 'danger',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::BAJA => 1,
            self::MEDIA => 2,
            self::ALTA => 3,
            self::CRITICA => 4,
        };
    }
}
