<?php

declare(strict_types=1);

namespace App\Domain\Reservations\Services;

use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Loans\Enums\LoanStatus;
use App\Domain\Loans\Models\Loan;
use App\Domain\Reservations\DTOs\ApproveReservationData;
use App\Domain\Reservations\DTOs\CreateReservationData;
use App\Domain\Reservations\Enums\ReservationStatus;
use App\Domain\Reservations\Models\Reservation;
use App\Domain\Reservations\Repositories\ReservationRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findById(int $id): ?Reservation
    {
        return $this->repository->findById($id);
    }

    public function create(CreateReservationData $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            // Validar que las fechas sean válidas
            $startDate = Carbon::parse($data->startDate);
            $endDate = Carbon::parse($data->endDate);

            if ($startDate->isPast()) {
                throw new \Exception('La fecha de inicio no puede ser en el pasado');
            }

            if ($endDate->isBefore($startDate)) {
                throw new \Exception('La fecha de fin debe ser posterior a la fecha de inicio');
            }

            if ($startDate->diffInDays($endDate) > 30) {
                throw new \Exception('La reserva no puede exceder 30 días');
            }

            // Validar que el equipo exista
            $equipment = Equipment::findOrFail($data->equipmentId);

            // Validar que no haya conflictos con otras reservas
            $conflicts = $this->repository->findConflictingReservations(
                $data->equipmentId,
                $data->startDate,
                $data->endDate
            );

            if ($conflicts->isNotEmpty()) {
                throw new \Exception('El equipo ya tiene una reserva en las fechas seleccionadas');
            }

            // Validar límite de reservas activas del usuario (máximo 3)
            $activeReservations = $this->repository->countActiveByUser($data->userId);
            if ($activeReservations >= 3) {
                throw new \Exception('El usuario ya tiene 3 reservas activas');
            }

            $reservation = $this->repository->create([
                ...$data->toArray(),
                'status' => ReservationStatus::PENDING,
            ]);

            return $reservation->load(['user', 'equipment']);
        });
    }

    public function approve(int $id, ApproveReservationData $data): Reservation
    {
        return DB::transaction(function () use ($id, $data) {
            $reservation = $this->repository->findById($id);

            if (!$reservation) {
                throw new \Exception('Reserva no encontrada');
            }

            if ($reservation->status !== ReservationStatus::PENDING) {
                throw new \Exception('Solo se pueden aprobar reservas pendientes');
            }

            // Volver a validar conflictos
            $conflicts = $this->repository->findConflictingReservations(
                $reservation->equipment_id,
                $reservation->start_date->toDateString(),
                $reservation->end_date->toDateString(),
                $id
            );

            if ($conflicts->isNotEmpty()) {
                throw new \Exception('El equipo ya tiene una reserva aprobada en las fechas seleccionadas');
            }

            $this->repository->update($reservation, [
                'status' => ReservationStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $data->approvedBy,
                'notes' => $data->notes ?? $reservation->notes,
            ]);

            return $reservation->fresh()->load(['user', 'equipment', 'approvedBy']);
        });
    }

    public function reject(int $id, int $rejectedBy, string $reason): Reservation
    {
        return DB::transaction(function () use ($id, $rejectedBy, $reason) {
            $reservation = $this->repository->findById($id);

            if (!$reservation) {
                throw new \Exception('Reserva no encontrada');
            }

            if ($reservation->status !== ReservationStatus::PENDING) {
                throw new \Exception('Solo se pueden rechazar reservas pendientes');
            }

            $this->repository->update($reservation, [
                'status' => ReservationStatus::REJECTED,
                'rejection_reason' => $reason,
                'approved_by' => $rejectedBy,
            ]);

            return $reservation->fresh()->load(['user', 'equipment', 'approvedBy']);
        });
    }

    public function cancel(int $id, int $cancelledBy, string $reason): Reservation
    {
        return DB::transaction(function () use ($id, $cancelledBy, $reason) {
            $reservation = $this->repository->findById($id);

            if (!$reservation) {
                throw new \Exception('Reserva no encontrada');
            }

            if (!$reservation->canBeCancelled()) {
                throw new \Exception('Esta reserva no puede ser cancelada');
            }

            $this->repository->update($reservation, [
                'status' => ReservationStatus::CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            return $reservation->fresh()->load(['user', 'equipment']);
        });
    }

    public function activate(int $id): Reservation
    {
        return DB::transaction(function () use ($id) {
            $reservation = $this->repository->findById($id);

            if (!$reservation) {
                throw new \Exception('Reserva no encontrada');
            }

            if (!$reservation->canBeActivated()) {
                throw new \Exception('Esta reserva no puede ser activada');
            }

            // Verificar que el equipo esté disponible
            $equipment = Equipment::findOrFail($reservation->equipment_id);
            if ($equipment->status !== EquipmentStatus::AVAILABLE) {
                throw new \Exception('El equipo no está disponible');
            }

            $this->repository->update($reservation, [
                'status' => ReservationStatus::ACTIVE,
            ]);

            // Actualizar estado del equipo a EN_USO
            $equipment->update(['status' => EquipmentStatus::ON_LOAN]);

            return $reservation->fresh()->load(['user', 'equipment']);
        });
    }

    public function complete(int $id): Reservation
    {
        return DB::transaction(function () use ($id) {
            $reservation = $this->repository->findById($id);

            if (!$reservation) {
                throw new \Exception('Reserva no encontrada');
            }

            if (!$reservation->canBeCompleted()) {
                throw new \Exception('Esta reserva no puede ser completada');
            }

            $this->repository->update($reservation, [
                'status' => ReservationStatus::COMPLETED,
            ]);

            // Si el equipo está en uso, marcarlo como disponible
            $equipment = Equipment::findOrFail($reservation->equipment_id);
            if ($equipment->status === EquipmentStatus::ON_LOAN) {
                $equipment->update(['status' => EquipmentStatus::AVAILABLE]);
            }

            return $reservation->fresh()->load(['user', 'equipment']);
        });
    }

    public function convertToLoan(int $id): Loan
    {
        return DB::transaction(function () use ($id) {
            $reservation = $this->repository->findById($id);

            if (!$reservation) {
                throw new \Exception('Reserva no encontrada');
            }

            if (!$reservation->canBeConvertedToLoan()) {
                throw new \Exception('Esta reserva no puede ser convertida a préstamo');
            }

            // Verificar que el equipo esté disponible
            $equipment = Equipment::findOrFail($reservation->equipment_id);
            if ($equipment->status !== EquipmentStatus::AVAILABLE) {
                throw new \Exception('El equipo no está disponible');
            }

            // Crear el préstamo
            $loan = Loan::create([
                'user_id' => $reservation->user_id,
                'equipment_id' => $reservation->equipment_id,
                'status' => LoanStatus::APPROVED,
                'requested_at' => now(),
                'approved_at' => now(),
                'approved_by' => $reservation->approved_by,
                'expected_return_date' => $reservation->end_date,
                'notes' => $reservation->notes,
            ]);

            // Actualizar la reserva
            $this->repository->update($reservation, [
                'status' => ReservationStatus::COMPLETED,
                'converted_loan_id' => $loan->id,
            ]);

            // Actualizar estado del equipo
            $equipment->update(['status' => EquipmentStatus::ON_LOAN]);

            return $loan->load(['user', 'equipment', 'approvedBy']);
        });
    }

    public function checkAndExpireReservations(): int
    {
        $expired = $this->repository->getExpired();
        $count = 0;

        foreach ($expired as $reservation) {
            $this->repository->update($reservation, [
                'status' => ReservationStatus::EXPIRED,
            ]);
            $count++;
        }

        return $count;
    }

    public function getByUser(int $userId, array $filters = []): Collection
    {
        return $this->repository->getByUser($userId, $filters);
    }

    public function getPending(): Collection
    {
        return $this->repository->getPending();
    }

    public function getByEquipment(int $equipmentId, ?ReservationStatus $status = null): Collection
    {
        return $this->repository->getByEquipment($equipmentId, $status);
    }

    public function delete(int $id): bool
    {
        $reservation = $this->repository->findById($id);

        if (!$reservation) {
            throw new \Exception('Reserva no encontrada');
        }

        return $this->repository->delete($reservation);
    }
}
