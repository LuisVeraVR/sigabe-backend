<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Enums;

enum MaintenancePriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::LOW => 'Baja',
            self::MEDIUM => 'Media',
            self::HIGH => 'Alta',
            self::CRITICAL => 'Crítica',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW => 'gray',
            self::MEDIUM => 'blue',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }

    public function urgencyDays(): int
    {
        return match($this) {
            self::LOW => 30,
            self::MEDIUM => 14,
            self::HIGH => 7,
            self::CRITICAL => 1,
        };
    }
}
