<?php

declare(strict_types=1);

namespace App\Domain\Spaces\Enums;

enum SpaceType: string
{
    case CLASSROOM = 'classroom';
    case LAB = 'lab';
    case AUDITORIUM = 'auditorium';
    case MEETING_ROOM = 'meeting_room';
    case LIBRARY = 'library';
    case STORAGE = 'storage';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CLASSROOM => 'Aula',
            self::LAB => 'Laboratorio',
            self::AUDITORIUM => 'Auditorio',
            self::MEETING_ROOM => 'Sala de Reuniones',
            self::LIBRARY => 'Biblioteca',
            self::STORAGE => 'Almacén',
            self::OTHER => 'Otro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CLASSROOM => 'school',
            self::LAB => 'science',
            self::AUDITORIUM => 'theater_comedy',
            self::MEETING_ROOM => 'meeting_room',
            self::LIBRARY => 'local_library',
            self::STORAGE => 'inventory_2',
            self::OTHER => 'room',
        };
    }
}
