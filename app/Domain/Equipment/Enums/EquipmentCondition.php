<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Enums;

enum EquipmentCondition: string
{
    case EXCELLENT = 'excellent';
    case GOOD      = 'good';
    case FAIR      = 'fair';
    case POOR = 'poor';
    case DAMAGED   = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Excelente',
            self::GOOD      => 'Buena',
            self::FAIR      => 'Regular',
            self::DAMAGED   => 'Dañado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EXCELLENT => 'green',
            self::GOOD      => 'blue',
            self::FAIR      => 'yellow',
            self::DAMAGED   => 'red',
        };
    }

    public function isOperational(): bool
    {
        return match ($this) {
            self::EXCELLENT,
            self::GOOD,
            self::FAIR => true,

            self::DAMAGED => false,
        };
    }
}
