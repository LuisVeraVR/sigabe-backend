<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Loans\Enums\LoanStatus;
use App\Domain\Loans\Models\Loan;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'equipment_id' => Equipment::factory(),
            'status' => fake()->randomElement(LoanStatus::cases()),
            'requested_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'expected_return_date' => fake()->dateTimeBetween('now', '+30 days'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Estado: Pendiente de aprobación
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::PENDING,
            'approved_at' => null,
            'approved_by' => null,
            'actual_return_date' => null,
        ]);
    }

    /**
     * Estado: Aprobado y activo
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::APPROVED,
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'approved_by' => User::factory(),
            'actual_return_date' => null,
        ]);
    }

    /**
     * Estado: Devuelto
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::RETURNED,
            'approved_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
            'approved_by' => User::factory(),
            'actual_return_date' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Estado: Rechazado
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::REJECTED,
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'approved_by' => User::factory(),
            'rejection_reason' => fake()->sentence(),
            'actual_return_date' => null,
        ]);
    }

    /**
     * Estado: Vencido
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::OVERDUE,
            'approved_at' => fake()->dateTimeBetween('-30 days', '-15 days'),
            'approved_by' => User::factory(),
            'expected_return_date' => fake()->dateTimeBetween('-14 days', '-1 day'),
            'actual_return_date' => null,
        ]);
    }
}
