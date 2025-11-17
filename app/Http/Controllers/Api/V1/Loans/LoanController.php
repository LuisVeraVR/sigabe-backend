<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loans;

use App\Domain\Loans\DTOs\CreateLoanData;
use App\Domain\Loans\Models\Loan;
use App\Domain\Loans\Services\LoanService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loans\CreateLoanRequest;
use App\Http\Requests\Loans\RejectLoanRequest;
use App\Http\Resources\Loans\LoanResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly LoanService $service
    ) {}

    /**
     * Listar préstamos con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('loans.view');

        $filters = $request->only(['status', 'user_id', 'equipment_id', 'sort_by', 'sort_order']);
        $perPage = (int) $request->input('per_page', 15);
        $loans = $this->service->list($filters, $perPage);

        return $this->paginatedResponse(
            $loans->setCollection(
                $loans->getCollection()->map(fn($item) => new LoanResource($item))
            ),
            'Préstamos obtenidos exitosamente'
        );
    }

    /**
     * Ver detalle de un préstamo
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('loans.view');

        $loan = $this->service->findById($id);

        if (!$loan) {
            return $this->notFoundResponse('Préstamo no encontrado');
        }

        return $this->successResponse(
            data: new LoanResource($loan),
            message: 'Préstamo obtenido exitosamente'
        );
    }

    /**
     * Crear solicitud de préstamo
     */
    public function store(CreateLoanRequest $request): JsonResponse
    {
        try {
            $data = CreateLoanData::fromRequest(
                $request->validated(),
                $request->user()->id
            );

            $loan = $this->service->create($data);

            return $this->createdResponse(
                data: new LoanResource($loan),
                message: 'Solicitud de préstamo creada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Aprobar préstamo
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorize('loans.approve');

        try {
            $loan = $this->service->approve($id, $request->user()->id);

            return $this->successResponse(
                data: new LoanResource($loan),
                message: 'Préstamo aprobado exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rechazar préstamo
     */
    public function reject(RejectLoanRequest $request, int $id): JsonResponse
    {
        $this->authorize('loans.approve');

        try {
            $loan = $this->service->reject(
                $id,
                $request->user()->id,
                $request->input('rejection_reason')
            );

            return $this->successResponse(
                data: new LoanResource($loan),
                message: 'Préstamo rechazado'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Devolver equipo prestado
     */
    public function return(Request $request, int $id): JsonResponse
    {
        $this->authorize('loans.return');

        try {
            $loan = $this->service->return($id);

            return $this->successResponse(
                data: new LoanResource($loan),
                message: 'Equipo devuelto exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener préstamos activos del usuario autenticado
     */
    public function myLoans(Request $request): JsonResponse
    {
        $loans = $this->service->getActiveByUser($request->user()->id);

        return $this->successResponse(
            data: LoanResource::collection($loans),
            message: 'Préstamos activos obtenidos exitosamente'
        );
    }

    /**
     * Obtener préstamos pendientes
     */
    public function pending(): JsonResponse
    {
        $this->authorize('loans.approve');

        $loans = $this->service->getPending();

        return $this->successResponse(
            data: LoanResource::collection($loans),
            message: 'Préstamos pendientes obtenidos exitosamente'
        );
    }

    /**
     * Obtener préstamos vencidos
     */
    public function overdue(): JsonResponse
    {
        $this->authorize('loans.view');

        $loans = $this->service->getOverdue();

        return $this->successResponse(
            data: LoanResource::collection($loans),
            message: 'Préstamos vencidos obtenidos exitosamente'
        );
    }

    /**
     * Obtener estadísticas de préstamos
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('loans.view');

        $stats = $this->service->getStatistics();

        return $this->successResponse(
            data: $stats,
            message: 'Estadísticas obtenidas exitosamente'
        );
    }
}
