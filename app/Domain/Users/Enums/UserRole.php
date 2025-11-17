<?php

declare(strict_types=1);

namespace App\Domain\Users\Enums;

enum UserRole: string
{
    case ADMIN_PROGRAMADOR = 'admin_programador';
    case ADMIN_MEDIATECA = 'admin_mediateca';
    case COLABORADOR = 'colaborador';
    case USUARIO = 'usuario';

    /**
     * Obtener etiqueta legible
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN_PROGRAMADOR => 'Administrador Programador',
            self::ADMIN_MEDIATECA => 'Administrador de Mediateca',
            self::COLABORADOR => 'Colaborador Técnico',
            self::USUARIO => 'Usuario',
        };
    }

    /**
     * Obtener descripción del rol
     */
    public function description(): string
    {
        return match($this) {
            self::ADMIN_PROGRAMADOR => 'Control total del sistema, configuración y parametrización',
            self::ADMIN_MEDIATECA => 'Gestión de catálogo, préstamos, reservas y reportes',
            self::COLABORADOR => 'Gestión de estados de equipos, mantenimiento y soporte técnico',
            self::USUARIO => 'Reservar espacios, solicitar préstamos y consultar catálogo',
        };
    }

    /**
     * Obtener todos los roles como array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verificar si es un rol administrativo
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN_PROGRAMADOR, self::ADMIN_MEDIATECA]);
    }
}
