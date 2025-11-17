<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reservations;

use App\Domain\Reservations\DTOs\ApproveReservationData;
use App\Domain\Reservations\DTOs\CreateReservationData;
use App\Domain\Reservations\Services\ReservationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\ApproveReservationRequest;
use App\Http\Requests\Reservations\CancelReservationRequest;
use App\Http\Requests\Reservations\CreateReservationRequest;
use App\Http\Requests\Reservations\RejectReservationRequest;
use App\Http\Resources\Loans\LoanResource;
use App\Http\Resources\Reservations\ReservationResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly ReservationService $service
    ) {}

    /**
     * Listar reservas con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('reservations.view');

        $filters = $request->only(['status', 'user_id', 'equipment_id', 'start_date', 'end_date']);
        $perPage = (int) $request->input('per_page', 15);
        $reservations = $this->service->paginate($filters, $perPage);

        return $this->paginatedResponse(
            $reservations->setCollection(
                $reservations->getCollection()->map(fn ($item) => new ReservationResource($item))
            ),
            'Reservas obtenidas exitosamente'
        );
    }

    /**
     * Ver detalle de una reserva
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('reservations.view');

        $reservation = $this->service->findById($id);

        if (!$reservation) {
            return $this->notFoundResponse('Reserva no encontrada');
        }

        return $this->successResponse(
            data: new ReservationResource($reservation),
            message: 'Reserva obtenida exitosamente'
        );
    }

    /**
     * Crear solicitud de reserva
     */
    public function store(CreateReservationRequest $request): JsonResponse
    {
        try {
            $data = CreateReservationData::fromRequest(
                $request->validated(),
                $request->user()->id
            );

            $reservation = $this->service->create($data);

            return $this->createdResponse(
                data: new ReservationResource($reservation),
                message: 'Solicitud de reserva creada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Aprobar reserva
     */
    public function approve(ApproveReservationRequest $request, int $id): JsonResponse
    {
        $this->authorize('reservations.approve');

        try {
            $data = ApproveReservationData::fromRequest(
                $request->validated(),
                $request->user()->id
            );

            $reservation = $this->service->approve($id, $data);

            return $this->successResponse(
                data: new ReservationResource($reservation),
                message: 'Reserva aprobada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rechazar reserva
     */
    public function reject(RejectReservationRequest $request, int $id): JsonResponse
    {
        $this->authorize('reservations.approve');

        try {
            $reservation = $this->service->reject(
                $id,
                $request->user()->id,
                $request->input('rejection_reason')
            );

            return $this->successResponse(
                data: new ReservationResource($reservation),
                message: 'Reserva rechazada'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancelar reserva
     */
    public function cancel(CancelReservationRequest $request, int $id): JsonResponse
    {
        try {
            $reservation = $this->service->cancel(
                $id,
                $request->user()->id,
                $request->input('cancellation_reason')
            );

            return $this->successResponse(
                data: new ReservationResource($reservation),
                message: 'Reserva cancelada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activar reserva (al inicio de la fecha programada)
     */
    public function activate(int $id): JsonResponse
    {
        $this->authorize('reservations.approve');

        try {
            $reservation = $this->service->activate($id);

            return $this->successResponse(
                data: new ReservationResource($reservation),
                message: 'Reserva activada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Completar reserva
     */
    public function complete(int $id): JsonResponse
    {
        $this->authorize('reservations.approve');

        try {
            $reservation = $this->service->complete($id);

            return $this->successResponse(
                data: new ReservationResource($reservation),
                message: 'Reserva completada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Convertir reserva a préstamo
     */
    public function convertToLoan(int $id): JsonResponse
    {
        $this->authorize('reservations.approve');

        try {
            $loan = $this->service->convertToLoan($id);

            return $this->successResponse(
                data: new LoanResource($loan),
                message: 'Reserva convertida a préstamo exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener reservas del usuario autenticado
     */
    public function myReservations(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $reservations = $this->service->getByUser($request->user()->id, $filters);

        return $this->successResponse(
            data: ReservationResource::collection($reservations),
            message: 'Reservas obtenidas exitosamente'
        );
    }

    /**
     * Obtener reservas pendientes
     */
    public function pending(): JsonResponse
    {
        $this->authorize('reservations.approve');

        $reservations = $this->service->getPending();

        return $this->successResponse(
            data: ReservationResource::collection($reservations),
            message: 'Reservas pendientes obtenidas exitosamente'
        );
    }

    /**
     * Eliminar reserva (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('reservations.approve');

        try {
            $this->service->delete($id);

            return $this->successResponse(
                message: 'Reserva eliminada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
