<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Reservations\Enums\ReservationStatus;
use App\Domain\Reservations\Models\Reservation;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $endDate = fake()->dateTimeBetween($startDate, '+60 days');

        return [
            'user_id' => User::factory(),
            'equipment_id' => Equipment::factory(),
            'status' => fake()->randomElement(ReservationStatus::cases()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Estado: Pendiente de aprobación
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::PENDING,
            'approved_at' => null,
            'approved_by' => null,
            'cancellation_reason' => null,
            'rejection_reason' => null,
            'converted_loan_id' => null,
        ]);
    }

    /**
     * Estado: Aprobada
     */
    public function approved(): static
    {
        $startDate = fake()->dateTimeBetween('+1 day', '+15 days');
        $endDate = fake()->dateTimeBetween($startDate, '+30 days');

        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::APPROVED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'approved_by' => User::factory(),
            'cancellation_reason' => null,
            'rejection_reason' => null,
            'converted_loan_id' => null,
        ]);
    }

    /**
     * Estado: Activa
     */
    public function active(): static
    {
        $startDate = fake()->dateTimeBetween('-3 days', 'now');
        $endDate = fake()->dateTimeBetween('now', '+7 days');

        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::ACTIVE,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'approved_at' => fake()->dateTimeBetween('-10 days', '-3 days'),
            'approved_by' => User::factory(),
            'cancellation_reason' => null,
            'rejection_reason' => null,
            'converted_loan_id' => null,
        ]);
    }

    /**
     * Estado: Completada
     */
    public function completed(): static
    {
        $startDate = fake()->dateTimeBetween('-30 days', '-7 days');
        $endDate = fake()->dateTimeBetween($startDate, '-1 day');

        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::COMPLETED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'approved_at' => fake()->dateTimeBetween('-40 days', '-30 days'),
            'approved_by' => User::factory(),
            'cancellation_reason' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Estado: Cancelada
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::CANCELLED,
            'cancellation_reason' => fake()->sentence(),
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
            'converted_loan_id' => null,
        ]);
    }

    /**
     * Estado: Rechazada
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::REJECTED,
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'approved_by' => User::factory(),
            'rejection_reason' => fake()->sentence(),
            'cancellation_reason' => null,
            'converted_loan_id' => null,
        ]);
    }

    /**
     * Estado: Expirada
     */
    public function expired(): static
    {
        $startDate = fake()->dateTimeBetween('-7 days', '-1 day');
        $endDate = fake()->dateTimeBetween($startDate, '+7 days');

        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::EXPIRED,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'approved_at' => fake()->dateTimeBetween('-15 days', '-7 days'),
            'approved_by' => User::factory(),
            'cancellation_reason' => null,
            'rejection_reason' => null,
            'converted_loan_id' => null,
        ]);
    }
}
