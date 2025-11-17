<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Equipment;

use App\Domain\Equipment\DTOs\CreateMaintenanceData;
use App\Domain\Equipment\Services\EquipmentMaintenanceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\CompleteMaintenanceRequest;
use App\Http\Requests\Equipment\CreateMaintenanceRequest;
use App\Http\Resources\Equipment\EquipmentMaintenanceResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentMaintenanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EquipmentMaintenanceService $service
    ) {}

    /**
     * Listar mantenimientos con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'equipment_id',
            'maintenance_type',
            'status',
            'priority',
            'technician_id',
            'from_date',
            'to_date',
            'sort_by',
            'sort_order',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $maintenances = $this->service->list($filters, $perPage);

        return $this->paginatedResponse(
            $maintenances->setCollection(
                $maintenances->getCollection()->map(fn($item) => new EquipmentMaintenanceResource($item))
            ),
            'Mantenimientos obtenidos exitosamente'
        );
    }

    /**
     * Ver detalle de un mantenimiento
     */
    public function show(int $id): JsonResponse
    {
        $maintenance = $this->service->findById($id);

        if (!$maintenance) {
            return $this->notFoundResponse('Mantenimiento no encontrado');
        }

        return $this->successResponse(
            data: new EquipmentMaintenanceResource($maintenance),
            message: 'Mantenimiento obtenido exitosamente'
        );
    }

    /**
     * Crear nuevo mantenimiento
     */
    public function store(CreateMaintenanceRequest $request): JsonResponse
    {
        $data = CreateMaintenanceData::fromRequest($request->validated());
        $maintenance = $this->service->create($data);

        return $this->createdResponse(
            data: new EquipmentMaintenanceResource($maintenance),
            message: 'Mantenimiento programado exitosamente'
        );
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $maintenance = $this->service->findById($id);

        if (!$maintenance) {
            return $this->notFoundResponse('Mantenimiento no encontrado');
        }

        // Validar que no esté completado
        if ($maintenance->status->value === 'completed') {
            return $this->errorResponse(
                'No se puede actualizar un mantenimiento completado',
                400
            );
        }

        $updated = $this->service->update($maintenance, $request->all());

        return $this->successResponse(
            data: new EquipmentMaintenanceResource($updated),
            message: 'Mantenimiento actualizado exitosamente'
        );
    }

    /**
     * Iniciar mantenimiento
     */
    public function start(int $id): JsonResponse
    {
        $maintenance = $this->service->findById($id);

        if (!$maintenance) {
            return $this->notFoundResponse('Mantenimiento no encontrado');
        }

        if ($maintenance->status->value !== 'scheduled') {
            return $this->errorResponse(
                'Solo se pueden iniciar mantenimientos programados',
                400
            );
        }

        $started = $this->service->start($maintenance);

        return $this->successResponse(
            data: new EquipmentMaintenanceResource($started),
            message: 'Mantenimiento iniciado exitosamente'
        );
    }

    /**
     * Completar mantenimiento
     */
    public function complete(CompleteMaintenanceRequest $request, int $id): JsonResponse
    {
        $maintenance = $this->service->findById($id);

        if (!$maintenance) {
            return $this->notFoundResponse('Mantenimiento no encontrado');
        }

        if (!in_array($maintenance->status->value, ['scheduled', 'in_progress'])) {
            return $this->errorResponse(
                'Solo se pueden completar mantenimientos programados o en progreso',
                400
            );
        }

        $completed = $this->service->complete(
            $maintenance,
            $request->input('actions_taken'),
            $request->input('cost')
        );

        // Actualizar partes reemplazadas si existen
        if ($request->has('parts_replaced')) {
            $completed->update(['parts_replaced' => $request->input('parts_replaced')]);
        }

        return $this->successResponse(
            data: new EquipmentMaintenanceResource($completed),
            message: 'Mantenimiento completado exitosamente'
        );
    }

    /**
     * Cancelar mantenimiento
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $maintenance = $this->service->findById($id);

        if (!$maintenance) {
            return $this->notFoundResponse('Mantenimiento no encontrado');
        }

        if ($maintenance->status->value === 'completed') {
            return $this->errorResponse(
                'No se puede cancelar un mantenimiento completado',
                400
            );
        }

        $cancelled = $this->service->cancel($maintenance, $request->input('reason'));

        return $this->successResponse(
            data: new EquipmentMaintenanceResource($cancelled),
            message: 'Mantenimiento cancelado exitosamente'
        );
    }

    /**
     * Obtener mantenimientos próximos
     */
    public function upcoming(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);
        $maintenances = $this->service->getUpcoming($days);

        return $this->successResponse(
            data: EquipmentMaintenanceResource::collection($maintenances),
            message: 'Mantenimientos próximos obtenidos exitosamente'
        );
    }

    /**
     * Obtener mantenimientos vencidos
     */
    public function overdue(): JsonResponse
    {
        $maintenances = $this->service->getOverdue();

        return $this->successResponse(
            data: EquipmentMaintenanceResource::collection($maintenances),
            message: 'Mantenimientos vencidos obtenidos exitosamente'
        );
    }

    /**
     * Obtener estadísticas de mantenimientos
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
