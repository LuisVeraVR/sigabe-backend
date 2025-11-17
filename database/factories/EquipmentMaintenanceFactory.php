<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Enums\MaintenancePriority;
use App\Domain\Equipment\Enums\MaintenanceStatus;
use App\Domain\Equipment\Enums\MaintenanceType;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentMaintenance;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentMaintenanceFactory extends Factory
{
    protected $model = EquipmentMaintenance::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'performed_by_user_id' => User::factory(),
            'maintenance_type' => fake()->randomElement(MaintenanceType::cases()),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'scheduled_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement(MaintenanceStatus::cases()),
            'priority' => fake()->randomElement(MaintenancePriority::cases()),
        ];
    }
}
