<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Equipment;

use App\Domain\Equipment\DTOs\CreateEquipmentData;
use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Services\EquipmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\ChangeEquipmentStatusRequest;
use App\Http\Requests\Equipment\CreateEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Resources\Equipment\EquipmentResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EquipmentController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function __construct(
        private readonly EquipmentService $service
    ) {}

    /**
     * Listar equipos con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'type_id',
            'brand_id',
            'status',
            'condition',
            'available',
            'under_warranty',
            'sort_by',
            'sort_order',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $equipment = $this->service->list($filters, $perPage);

        return $this->paginatedResponse(
            $equipment->setCollection(
                $equipment->getCollection()->map(fn($item) => new EquipmentResource($item))
            ),
            'Equipos obtenidos exitosamente'
        );
    }

    /**
     * Ver detalle de un equipo
     */
    public function show(int $id): JsonResponse
    {
        $equipment = $this->service->findById($id);

        if (!$equipment) {
            return $this->notFoundResponse('Equipo no encontrado');
        }

        return $this->successResponse(
            data: new EquipmentResource($equipment),
            message: 'Equipo obtenido exitosamente'
        );
    }

    /**
     * Crear nuevo equipo
     */
    public function store(CreateEquipmentRequest $request): JsonResponse
    {
        $data = CreateEquipmentData::fromRequest($request->validated());
        $equipment = $this->service->create($data);

        return $this->createdResponse(
            data: new EquipmentResource($equipment),
            message: 'Equipo creado exitosamente'
        );
    }

    /**
     * Actualizar equipo
     */
    public function update(UpdateEquipmentRequest $request, int $id): JsonResponse
    {
        $equipment = $this->service->findById($id);

        if (!$equipment) {
            return $this->notFoundResponse('Equipo no encontrado');
        }

        $updated = $this->service->update($equipment, $request->validated());

        return $this->successResponse(
            data: new EquipmentResource($updated),
            message: 'Equipo actualizado exitosamente'
        );
    }

    /**
     * Eliminar equipo
     */
    public function destroy(int $id): JsonResponse
    {
        $equipment = $this->service->findById($id);

        if (!$equipment) {
            return $this->notFoundResponse('Equipo no encontrado');
        }

        if ($equipment->status === EquipmentStatus::ON_LOAN) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un equipo que está en préstamo',
            ], 400);
        }

        $this->service->delete($equipment);

        return response()->json([
            'success' => true,
            'message' => 'Equipo eliminado exitosamente',
        ]);
    }

    /**
     * Cambiar estado del equipo
     */
    public function changeStatus(ChangeEquipmentStatusRequest $request, int $id): JsonResponse
    {
        $equipment = $this->service->findById($id);

        if (!$equipment) {
            return $this->notFoundResponse('Equipo no encontrado');
        }

        $newStatus = EquipmentStatus::from($request->input('status'));
        $reason = $request->input('reason');

        $updated = $this->service->changeStatus($equipment, $newStatus, $reason);

        return $this->successResponse(
            data: new EquipmentResource($updated),
            message: 'Estado del equipo actualizado exitosamente'
        );
    }

    /**
     * Verificar disponibilidad de un equipo
     */
    public function checkAvailability(int $id): JsonResponse
    {
        $availability = $this->service->checkAvailability($id);

        if (!$availability['available']) {
            return $this->successResponse(
                data: $availability,
                message: 'Equipo no disponible para préstamo'
            );
        }

        return $this->successResponse(
            data: [
                'available' => true,
                'equipment' => new EquipmentResource($availability['equipment']),
                'suggested_duration_hours' => $availability['suggested_duration_hours'],
            ],
            message: 'Equipo disponible para préstamo'
        );
    }

    /**
     * Obtener historial de un equipo
     */
    public function history(int $id): JsonResponse
    {
        $equipment = $this->service->findById($id);

        if (!$equipment) {
            return $this->notFoundResponse('Equipo no encontrado');
        }

        $history = $this->service->getHistory($id);

        return $this->successResponse(
            data: $history,
            message: 'Historial obtenido exitosamente'
        );
    }

    /**
     * Obtener estadísticas de equipos
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->service->getStatistics();

        return $this->successResponse(
            data: $stats,
            message: 'Estadísticas obtenidas exitosamente'
        );
    }
}
