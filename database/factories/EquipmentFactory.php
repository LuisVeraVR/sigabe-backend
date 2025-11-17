<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Enums\EquipmentCondition;
use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentBrand;
use App\Domain\Equipment\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    // ← AGREGAR ESTA VARIABLE ESTÁTICA
    private static int $assetCounter = 0;

    public function definition(): array
    {
        // ← GENERAR asset_code ÚNICO
        self::$assetCounter++;

        return [
            'equipment_type_id' => EquipmentType::factory(),
            'equipment_brand_id' => EquipmentBrand::factory(),
            'name' => fake()->words(3, true),
            'model' => strtoupper(fake()->bothify('??-####')),
            'serial_number' => strtoupper(fake()->bothify('SN-??########')),
            'asset_code' => 'EQ-2025-' . str_pad((string)self::$assetCounter, 4, '0', STR_PAD_LEFT), // ← GENERAR ÚNICO
            'purchase_date' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'purchase_cost' => fake()->randomFloat(2, 500000, 5000000),
            'supplier' => fake()->company(),
            'warranty_expiration_date' => fake()->dateTimeBetween('now', '+2 years'),
            'specifications' => [
                'ram' => fake()->randomElement(['4GB', '8GB', '16GB', '32GB']),
                'storage' => fake()->randomElement(['256GB', '512GB', '1TB']),
                'processor' => fake()->randomElement(['Intel i5', 'Intel i7', 'AMD Ryzen 5']),
            ],
            'condition' => fake()->randomElement(EquipmentCondition::cases()),
            'status' => fake()->randomElement([
                EquipmentStatus::AVAILABLE,
                EquipmentStatus::AVAILABLE,
                EquipmentStatus::AVAILABLE,
                EquipmentStatus::ON_LOAN,
                EquipmentStatus::MAINTENANCE,
            ]),
            'current_space_id' => null,
            'requires_accessories' => fake()->randomElement([
                ['Cargador', 'Cable HDMI'],
                ['Mouse', 'Teclado'],
                ['Cargador', 'Estuche'],
                null,
            ]),
        ];
    }
}
