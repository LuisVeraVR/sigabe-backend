<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_maintenances', function (Blueprint $table) {
            $table->id();

            // Relación con el equipo
            $table->foreignId('equipment_id')
                ->constrained('equipment')   // 👈 nombre de la tabla: equipment
                ->cascadeOnDelete();

            // Usuario que realiza el mantenimiento
            $table->foreignId('performed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Tipo de mantenimiento
            $table->enum('maintenance_type', [
                'preventive',
                'corrective',
                'cleaning',
                'software_update',
                'calibration',
                'inspection',
            ]);

            // Información del mantenimiento
            $table->string('title', 200);
            $table->text('description');
            $table->text('actions_taken')->nullable();

            // Fechas
            $table->date('scheduled_date');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('completion_date')->nullable();
            $table->date('next_maintenance_date')->nullable();

            // Costos y partes
            $table->decimal('cost', 10, 2)->nullable();
            $table->json('parts_replaced')->nullable();

            // Estado
            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('scheduled');

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                ->default('medium');

            $table->timestamps();

            // Índices
            $table->index('equipment_id');
            $table->index('performed_by_user_id');
            $table->index('maintenance_type');
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('next_maintenance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenances');
    }
};
