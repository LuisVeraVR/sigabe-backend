<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('equipment_id')
                ->constrained('equipment')
                ->onDelete('cascade');

            $table->foreignId('reported_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Información del incidente
            $table->string('title');
            $table->text('description');

            // Estado y prioridad
            $table->enum('status', [
                'reportado',
                'en_revision',
                'en_reparacion',
                'resuelto',
                'cerrado'
            ])->default('reportado');

            $table->enum('priority', [
                'baja',
                'media',
                'alta',
                'critica'
            ])->default('media');

            // Resolución
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar consultas
            $table->index('equipment_id');
            $table->index('reported_by');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index(['equipment_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
