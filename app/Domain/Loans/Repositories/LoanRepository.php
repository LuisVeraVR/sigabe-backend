<?php

declare(strict_types=1);

namespace App\Domain\Loans\Repositories;

use App\Domain\Loans\Enums\LoanStatus;
use App\Domain\Loans\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LoanRepository
{
    /**
     * Listar préstamos con filtros y paginación
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Loan::with(['user', 'equipment', 'approvedBy']);

        // Filtro por estado
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtro por usuario
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtro por equipo
        if (!empty($filters['equipment_id'])) {
            $query->where('equipment_id', $filters['equipment_id']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Buscar préstamo por ID con relaciones
     */
    public function findById(int $id): ?Loan
    {
        return Loan::query()
            ->with(['user', 'equipment', 'approvedBy'])
            ->find($id);
    }

    /**
     * Crear nuevo préstamo
     */
    public function create(array $data): Loan
    {
        return Loan::create($data);
    }

    /**
     * Actualizar préstamo
     */
    public function update(Loan $loan, array $data): bool
    {
        return $loan->update($data);
    }

    /**
     * Eliminar préstamo (soft delete)
     */
    public function delete(Loan $loan): bool
    {
        return (bool) $loan->delete();
    }

    /**
     * Obtener préstamos activos de un usuario
     */
    public function getActiveByUser(int $userId): Collection
    {
        return Loan::with(['equipment'])
            ->where('user_id', $userId)
            ->where('status', LoanStatus::APPROVED)
            ->get();
    }

    /**
     * Contar préstamos activos de un usuario
     */
    public function countActiveByUser(int $userId): int
    {
        return Loan::where('user_id', $userId)
            ->where('status', LoanStatus::APPROVED)
            ->count();
    }

    /**
     * Obtener préstamos pendientes
     */
    public function getPending(): Collection
    {
        return Loan::with(['user', 'equipment'])
            ->where('status', LoanStatus::PENDING)
            ->orderBy('requested_at', 'asc')
            ->get();
    }

    /**
     * Obtener préstamos vencidos
     */
    public function getOverdue(): Collection
    {
        return Loan::with(['user', 'equipment'])
            ->where('status', LoanStatus::APPROVED)
            ->where('expected_return_date', '<', now()->startOfDay())
            ->orderBy('expected_return_date', 'asc')
            ->get();
    }

    /**
     * Obtener historial de préstamos de un equipo
     */
    public function getHistoryByEquipment(int $equipmentId): Collection
    {
        return Loan::with(['user', 'approvedBy'])
            ->where('equipment_id', $equipmentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener historial de préstamos de un usuario
     */
    public function getHistoryByUser(int $userId): Collection
    {
        return Loan::with(['equipment', 'approvedBy'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas de préstamos
     */
    public function getStatistics(): array
    {
        return [
            'total' => Loan::count(),
            'pending' => Loan::where('status', LoanStatus::PENDING)->count(),
            'approved' => Loan::where('status', LoanStatus::APPROVED)->count(),
            'returned' => Loan::where('status', LoanStatus::RETURNED)->count(),
            'rejected' => Loan::where('status', LoanStatus::REJECTED)->count(),
            'overdue' => Loan::where('status', LoanStatus::APPROVED)
                ->where('expected_return_date', '<', now()->startOfDay())
                ->count(),
        ];
    }
}
