<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Enums;

enum MaintenanceType: string
{
    case PREVENTIVE = 'preventive';
    case CORRECTIVE = 'corrective';
    case CLEANING = 'cleaning';
    case SOFTWARE_UPDATE = 'software_update';
    case CALIBRATION = 'calibration';
    case INSPECTION = 'inspection';

    public function label(): string
    {
        return match($this) {
            self::PREVENTIVE => 'Preventivo',
            self::CORRECTIVE => 'Correctivo',
            self::CLEANING => 'Limpieza',
            self::SOFTWARE_UPDATE => 'Actualización de Software',
            self::CALIBRATION => 'Calibración',
            self::INSPECTION => 'Inspección',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PREVENTIVE => 'Mantenimiento programado para prevenir fallos',
            self::CORRECTIVE => 'Reparación de fallas o problemas detectados',
            self::CLEANING => 'Limpieza y mantenimiento básico',
            self::SOFTWARE_UPDATE => 'Actualización de software y firmware',
            self::CALIBRATION => 'Calibración de componentes',
            self::INSPECTION => 'Revisión general del equipo',
        };
    }
}
