<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Incidents\Enums\IncidentPriority;
use App\Domain\Incidents\Enums\IncidentStatus;
use App\Domain\Incidents\Models\Incident;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'reported_by' => User::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'status' => fake()->randomElement(IncidentStatus::cases()),
            'priority' => fake()->randomElement(IncidentPriority::cases()),
            'resolution_notes' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Estado: Reportado
     */
    public function reportado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::REPORTADO,
            'assigned_to' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Estado: En Revisión
     */
    public function enRevision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::EN_REVISION,
            'assigned_to' => User::factory(),
            'resolution_notes' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Estado: En Reparación
     */
    public function enReparacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::EN_REPARACION,
            'assigned_to' => User::factory(),
            'resolution_notes' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Estado: Resuelto
     */
    public function resuelto(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::RESUELTO,
            'assigned_to' => User::factory(),
            'resolution_notes' => fake()->paragraph(2),
            'resolved_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'closed_at' => null,
        ]);
    }

    /**
     * Estado: Cerrado
     */
    public function cerrado(): static
    {
        $resolvedAt = fake()->dateTimeBetween('-30 days', '-7 days');
        $closedAt = fake()->dateTimeBetween($resolvedAt, 'now');

        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::CERRADO,
            'assigned_to' => User::factory(),
            'resolution_notes' => fake()->paragraph(2),
            'resolved_at' => $resolvedAt,
            'closed_at' => $closedAt,
        ]);
    }

    /**
     * Prioridad: Baja
     */
    public function baja(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => IncidentPriority::BAJA,
        ]);
    }

    /**
     * Prioridad: Media
     */
    public function media(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => IncidentPriority::MEDIA,
        ]);
    }

    /**
     * Prioridad: Alta
     */
    public function alta(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => IncidentPriority::ALTA,
        ]);
    }

    /**
     * Prioridad: Crítica
     */
    public function critica(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => IncidentPriority::CRITICA,
        ]);
    }

    /**
     * Sin asignar
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => null,
        ]);
    }
}
