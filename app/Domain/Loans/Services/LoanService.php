<?php

declare(strict_types=1);

namespace App\Domain\Loans\Services;

use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Loans\DTOs\CreateLoanData;
use App\Domain\Loans\Enums\LoanStatus;
use App\Domain\Loans\Models\Loan;
use App\Domain\Loans\Repositories\LoanRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(
        private readonly LoanRepository $repository
    ) {}

    /**
     * Listar préstamos con filtros
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * Obtener préstamo por ID
     */
    public function findById(int $id): ?Loan
    {
        return $this->repository->findById($id);
    }

    /**
     * Crear solicitud de préstamo
     */
    public function create(CreateLoanData $data): Loan
    {
        return DB::transaction(function () use ($data) {
            // Validar que el equipo existe y está disponible
            $equipment = Equipment::findOrFail($data->equipmentId);

            if ($equipment->status !== EquipmentStatus::AVAILABLE) {
                throw new \Exception('El equipo no está disponible para préstamo');
            }

            // Validar que el usuario no tenga más de 3 préstamos activos
            $activeLoans = $this->repository->countActiveByUser($data->userId);

            if ($activeLoans >= 3) {
                throw new \Exception('El usuario ya tiene 3 préstamos activos. Debe devolver un equipo antes de solicitar otro.');
            }

            // Crear el préstamo
            $loan = $this->repository->create([
                ...$data->toArray(),
                'status' => LoanStatus::PENDING,
                'requested_at' => now(),
            ]);

            return $loan->load(['user', 'equipment']);
        });
    }

    /**
     * Aprobar préstamo
     */
    public function approve(int $loanId, int $approvedBy): Loan
    {
        return DB::transaction(function () use ($loanId, $approvedBy) {
            $loan = $this->repository->findById($loanId);

            if (!$loan) {
                throw new \Exception('Préstamo no encontrado');
            }

            if (!$loan->canBeApproved()) {
                throw new \Exception('Solo se pueden aprobar préstamos pendientes');
            }

            // Verificar nuevamente que el equipo esté disponible
            $equipment = $loan->equipment;
            if ($equipment->status !== EquipmentStatus::AVAILABLE) {
                throw new \Exception('El equipo ya no está disponible');
            }

            // Actualizar estado del equipo a "en préstamo"
            $equipment->update(['status' => EquipmentStatus::ON_LOAN]);

            // Actualizar préstamo
            $this->repository->update($loan, [
                'status' => LoanStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);

            return $loan->fresh();
        });
    }

    /**
     * Rechazar préstamo
     */
    public function reject(int $loanId, int $rejectedBy, string $reason): Loan
    {
        return DB::transaction(function () use ($loanId, $rejectedBy, $reason) {
            $loan = $this->repository->findById($loanId);

            if (!$loan) {
                throw new \Exception('Préstamo no encontrado');
            }

            if (!$loan->canBeRejected()) {
                throw new \Exception('Solo se pueden rechazar préstamos pendientes');
            }

            // Actualizar préstamo
            $this->repository->update($loan, [
                'status' => LoanStatus::REJECTED,
                'approved_by' => $rejectedBy,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $loan->fresh();
        });
    }

    /**
     * Devolver equipo prestado
     */
    public function return(int $loanId): Loan
    {
        return DB::transaction(function () use ($loanId) {
            $loan = $this->repository->findById($loanId);

            if (!$loan) {
                throw new \Exception('Préstamo no encontrado');
            }

            if (!$loan->canBeReturned()) {
                throw new \Exception('Solo se pueden devolver préstamos activos');
            }

            // Actualizar estado del equipo a disponible
            $loan->equipment->update(['status' => EquipmentStatus::AVAILABLE]);

            // Actualizar préstamo
            $this->repository->update($loan, [
                'status' => LoanStatus::RETURNED,
                'actual_return_date' => now(),
            ]);

            return $loan->fresh();
        });
    }

    /**
     * Obtener préstamos activos de un usuario
     */
    public function getActiveByUser(int $userId)
    {
        return $this->repository->getActiveByUser($userId);
    }

    /**
     * Obtener préstamos pendientes
     */
    public function getPending()
    {
        return $this->repository->getPending();
    }

    /**
     * Obtener préstamos vencidos
     */
    public function getOverdue()
    {
        return $this->repository->getOverdue();
    }

    /**
     * Marcar préstamos como vencidos
     */
    public function markOverdueLoans(): int
    {
        $overdueLoans = Loan::where('status', LoanStatus::APPROVED)
            ->where('expected_return_date', '<', now()->startOfDay())
            ->get();

        $count = 0;
        foreach ($overdueLoans as $loan) {
            $this->repository->update($loan, ['status' => LoanStatus::OVERDUE]);
            $count++;
        }

        return $count;
    }

    /**
     * Obtener estadísticas
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
