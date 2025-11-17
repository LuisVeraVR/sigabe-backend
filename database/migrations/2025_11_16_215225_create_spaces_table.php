<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();

            // Identificación del espacio (ambiente/sala)
            $table->string('code', 50)->unique();   // ej: "LAB-101", "MED-01"
            $table->string('name', 150);            // ej: "Laboratorio de Redes", "Mediateca Sala 1"

            // Ubicación
            $table->string('building')->nullable(); // Bloque / edificio
            $table->string('floor')->nullable();    // Piso
            $table->string('location_description')->nullable(); // Descripción más detallada

            // Capacidad y tipo
            $table->unsignedInteger('capacity')->nullable(); // Número de personas
            $table->enum('space_type', [
                'classroom',
                'lab',
                'auditorium',
                'meeting_room',
                'library',
                'storage',
                'other',
            ])->default('classroom');

            // Estado del espacio
            $table->enum('status', [
                'available',
                'unavailable',
                'maintenance',
                'reserved',
            ])->default('available');

            $table->text('description')->nullable();

            $table->timestamps();

            // Índices
            $table->index('code');
            $table->index('space_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
