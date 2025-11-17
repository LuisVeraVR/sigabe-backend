<?php

declare(strict_types=1);

namespace App\Domain\Reservations\Repositories;

use App\Domain\Reservations\Enums\ReservationStatus;
use App\Domain\Reservations\Models\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReservationRepository
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::with(['user', 'equipment', 'approvedBy', 'convertedLoan'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['equipment_id']), fn ($q) => $q->where('equipment_id', $filters['equipment_id']))
            ->when(isset($filters['start_date']), fn ($q) => $q->where('start_date', '>=', $filters['start_date']))
            ->when(isset($filters['end_date']), fn ($q) => $q->where('end_date', '<=', $filters['end_date']))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Reservation
    {
        return Reservation::with(['user', 'equipment', 'approvedBy', 'convertedLoan'])
            ->find($id);
    }

    public function create(array $data): Reservation
    {
        return Reservation::create($data);
    }

    public function update(Reservation $reservation, array $data): bool
    {
        return $reservation->update($data);
    }

    public function delete(Reservation $reservation): bool
    {
        return $reservation->delete();
    }

    public function getPending(): Collection
    {
        return Reservation::with(['user', 'equipment'])
            ->pending()
            ->orderBy('created_at')
            ->get();
    }

    public function getActiveByUser(int $userId): Collection
    {
        return Reservation::with(['equipment'])
            ->byUser($userId)
            ->whereIn('status', [
                ReservationStatus::PENDING,
                ReservationStatus::APPROVED,
                ReservationStatus::ACTIVE,
            ])
            ->orderBy('start_date')
            ->get();
    }

    public function countActiveByUser(int $userId): int
    {
        return Reservation::byUser($userId)
            ->whereIn('status', [
                ReservationStatus::PENDING,
                ReservationStatus::APPROVED,
                ReservationStatus::ACTIVE,
            ])
            ->count();
    }

    public function getByEquipment(int $equipmentId, ?ReservationStatus $status = null): Collection
    {
        return Reservation::with(['user'])
            ->byEquipment($equipmentId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('start_date')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return Reservation::with(['user', 'equipment'])
            ->byDateRange($startDate, $endDate)
            ->whereIn('status', [
                ReservationStatus::APPROVED,
                ReservationStatus::ACTIVE,
            ])
            ->orderBy('start_date')
            ->get();
    }

    public function getExpired(): Collection
    {
        return Reservation::with(['user', 'equipment'])
            ->approved()
            ->where('start_date', '<', now())
            ->get();
    }

    public function findConflictingReservations(
        int $equipmentId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): Collection {
        return Reservation::byEquipment($equipmentId)
            ->whereIn('status', [
                ReservationStatus::PENDING,
                ReservationStatus::APPROVED,
                ReservationStatus::ACTIVE,
            ])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->get();
    }

    public function getByUser(int $userId, array $filters = []): Collection
    {
        return Reservation::with(['equipment', 'approvedBy', 'convertedLoan'])
            ->byUser($userId)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAllByStatus(ReservationStatus $status): Collection
    {
        return Reservation::with(['user', 'equipment'])
            ->where('status', $status)
            ->orderBy('created_at')
            ->get();
    }
}
