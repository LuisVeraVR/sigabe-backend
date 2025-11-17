<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Models\EquipmentBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentBrandFactory extends Factory
{
    protected $model = EquipmentBrand::class;

    public function definition(): array
    {
        $brands = [
            ['name' => 'HP', 'country' => 'Estados Unidos'],
            ['name' => 'Dell', 'country' => 'Estados Unidos'],
            ['name' => 'Lenovo', 'country' => 'China'],
            ['name' => 'Epson', 'country' => 'Japón'],
            ['name' => 'Samsung', 'country' => 'Corea del Sur'],
            ['name' => 'LG', 'country' => 'Corea del Sur'],
        ];

        $brand = fake()->randomElement($brands);

        return [
            'name' => $brand['name'],
            'slug' => \Illuminate\Support\Str::slug($brand['name']),
            'logo_url' => null,
            'website' => 'https://www.' . strtolower($brand['name']) . '.com',
            'country' => $brand['country'],
            'is_active' => true,
        ];
    }
}
