<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;
use App\Domain\Spaces\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpaceFactory extends Factory
{
    protected $model = Space::class;

    public function definition(): array
    {
        $types = ['LAB', 'AUL', 'AUD', 'SAL', 'BIB', 'ALM'];
        $type = fake()->randomElement($types);
        $number = fake()->unique()->numberBetween(100, 999);

        return [
            'code' => "{$type}-{$number}",
            'name' => $this->generateName(),
            'building' => fake()->randomElement(['Edificio A', 'Edificio B', 'Edificio C', 'Bloque Principal', null]),
            'floor' => fake()->randomElement(['1', '2', '3', '4', 'PB', null]),
            'location_description' => fake()->optional(0.7)->sentence(),
            'capacity' => fake()->optional(0.8)->numberBetween(10, 100),
            'space_type' => fake()->randomElement(SpaceType::cases())->value,
            'status' => SpaceStatus::AVAILABLE->value,
            'description' => fake()->optional(0.6)->paragraph(),
        ];
    }

    private function generateName(): string
    {
        $prefixes = [
            'Aula',
            'Laboratorio',
            'Sala',
            'Auditorio',
            'Biblioteca',
            'Sala de',
        ];

        $suffixes = [
            'de Computo',
            'de Redes',
            'de Física',
            'de Química',
            'Principal',
            'Magna',
            'de Reuniones',
            'de Conferencias',
            'Multimedia',
            'de Idiomas',
        ];

        $prefix = fake()->randomElement($prefixes);
        $suffix = fake()->randomElement($suffixes);

        return "{$prefix} {$suffix}";
    }

    // ==================== State Methods ====================

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpaceStatus::AVAILABLE,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpaceStatus::UNAVAILABLE,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpaceStatus::MAINTENANCE,
        ]);
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpaceStatus::RESERVED,
        ]);
    }

    // ==================== Type Methods ====================

    public function classroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'space_type' => SpaceType::CLASSROOM,
            'name' => 'Aula ' . fake()->numberBetween(101, 505),
            'capacity' => fake()->numberBetween(20, 50),
        ]);
    }

    public function lab(): static
    {
        return $this->state(fn (array $attributes) => [
            'space_type' => SpaceType::LAB,
            'name' => 'Laboratorio de ' . fake()->randomElement(['Computo', 'Redes', 'Física', 'Química']),
            'capacity' => fake()->numberBetween(15, 30),
        ]);
    }

    public function auditorium(): static
    {
        return $this->state(fn (array $attributes) => [
            'space_type' => SpaceType::AUDITORIUM,
            'name' => 'Auditorio ' . fake()->randomElement(['Principal', 'Magna', 'Central']),
            'capacity' => fake()->numberBetween(100, 500),
        ]);
    }

    public function meetingRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'space_type' => SpaceType::MEETING_ROOM,
            'name' => 'Sala de Reuniones ' . fake()->numberBetween(1, 10),
            'capacity' => fake()->numberBetween(6, 20),
        ]);
    }

    public function library(): static
    {
        return $this->state(fn (array $attributes) => [
            'space_type' => SpaceType::LIBRARY,
            'name' => 'Biblioteca ' . fake()->randomElement(['Central', 'de Ciencias', 'de Humanidades']),
            'capacity' => fake()->numberBetween(50, 200),
        ]);
    }

    public function storage(): static
    {
        return $this->state(fn (array $attributes) => [
            'space_type' => SpaceType::STORAGE,
            'name' => 'Almacén ' . fake()->numberBetween(1, 10),
            'capacity' => null,
        ]);
    }

    // ==================== Helper Methods ====================

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }

    public function inBuilding(string $building): static
    {
        return $this->state(fn (array $attributes) => [
            'building' => $building,
        ]);
    }

    public function onFloor(string $floor): static
    {
        return $this->state(fn (array $attributes) => [
            'floor' => $floor,
        ]);
    }
}
