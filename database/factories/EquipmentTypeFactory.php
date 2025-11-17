<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentTypeFactory extends Factory
{
    protected $model = EquipmentType::class;

    private static int $counter = 0;

    public function definition(): array
    {
        $types = [
            ['name' => 'Computador Portátil', 'icon' => 'laptop', 'hours' => 48],
            ['name' => 'Proyector', 'icon' => 'projector', 'hours' => 8],
            ['name' => 'Tablet', 'icon' => 'tablet', 'hours' => 24],
            ['name' => 'Cámara Digital', 'icon' => 'camera', 'hours' => 8],
            ['name' => 'Micrófono', 'icon' => 'microphone', 'hours' => 8],
            ['name' => 'Parlantes', 'icon' => 'speaker', 'hours' => 8],
        ];

        $type = fake()->randomElement($types);
        self::$counter++;

        return [
            'name' => $type['name'] . ' ' . self::$counter,
            'slug' => \Illuminate\Support\Str::slug($type['name']) . '-' . self::$counter,
            'description' => fake()->sentence(),
            'icon' => $type['icon'],
            'requires_training' => fake()->boolean(30),
            'average_loan_duration_hours' => $type['hours'],
            'is_active' => true,
        ];
    }
}
