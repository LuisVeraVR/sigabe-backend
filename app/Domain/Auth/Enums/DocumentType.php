<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

enum DocumentType: string
{
    case CC = 'CC'; // Cédula de Ciudadanía
    case TI = 'TI'; // Tarjeta de Identidad
    case CE = 'CE'; // Cédula de Extranjería
    case PAS = 'PAS'; // Pasaporte

    public function label(): string
    {
        return match($this) {
            self::CC => 'Cédula de Ciudadanía',
            self::TI => 'Tarjeta de Identidad',
            self::CE => 'Cédula de Extranjería',
            self::PAS => 'Pasaporte',
        };
    }
}
