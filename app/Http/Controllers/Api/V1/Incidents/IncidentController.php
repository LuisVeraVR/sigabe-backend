<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Incidents;

use App\Domain\Incidents\DTOs\AssignIncidentData;
use App\Domain\Incidents\DTOs\CreateIncidentData;
use App\Domain\Incidents\DTOs\ResolveIncidentData;
use App\Domain\Incidents\Services\IncidentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Incidents\AssignIncidentRequest;
use App\Http\Requests\Incidents\CreateIncidentRequest;
use App\Http\Requests\Incidents\ResolveIncidentRequest;
use App\Http\Resources\Incidents\IncidentResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly IncidentService $service
    ) {}

    /**
     * Listar incidentes con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('incidents.view');

        $filters = $request->only(['status', 'priority', 'equipment_id', 'reported_by', 'assigned_to', 'unassigned']);
        $perPage = (int) $request->input('per_page', 15);
        $incidents = $this->service->paginate($filters, $perPage);

        return $this->paginatedResponse(
            $incidents->setCollection(
                $incidents->getCollection()->map(fn ($item) => new IncidentResource($item))
            ),
            'Incidentes obtenidos exitosamente'
        );
    }

    /**
     * Ver detalle de un incidente
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('incidents.view');

        $incident = $this->service->findById($id);

        if (!$incident) {
            return $this->notFoundResponse('Incidente no encontrado');
        }

        return $this->successResponse(
            data: new IncidentResource($incident),
            message: 'Incidente obtenido exitosamente'
        );
    }

    /**
     * Crear un incidente
     */
    public function store(CreateIncidentRequest $request): JsonResponse
    {
        try {
            $data = CreateIncidentData::fromRequest(
                $request->validated(),
                $request->user()->id
            );

            $incident = $this->service->create($data);

            return $this->createdResponse(
                data: new IncidentResource($incident),
                message: 'Incidente reportado exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Asignar incidente a un técnico
     */
    public function assign(AssignIncidentRequest $request, int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $data = AssignIncidentData::fromRequest($request->validated());
            $incident = $this->service->assign($id, $data);

            return $this->successResponse(
                data: new IncidentResource($incident),
                message: 'Incidente asignado exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Desasignar incidente
     */
    public function unassign(int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $incident = $this->service->unassign($id);

            return $this->successResponse(
                data: new IncidentResource($incident),
                message: 'Incidente desasignado exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Iniciar reparación
     */
    public function startRepair(int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $incident = $this->service->startRepair($id);

            return $this->successResponse(
                data: new IncidentResource($incident),
                message: 'Reparación iniciada exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resolver incidente
     */
    public function resolve(ResolveIncidentRequest $request, int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $data = ResolveIncidentData::fromRequest($request->validated());
            $incident = $this->service->resolve($id, $data);

            return $this->successResponse(
                data: new IncidentResource($incident),
                message: 'Incidente resuelto exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cerrar incidente
     */
    public function close(int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $incident = $this->service->close($id);

            return $this->successResponse(
                data: new IncidentResource($incident),
                message: 'Incidente cerrado exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reabrir incidente
     */
    public function reopen(int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $incident = $this->service->reopen($id);

            return $this->successResponse(
                data: new IncidentResource($incident),
                message: 'Incidente reabierto exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener incidentes activos
     */
    public function active(): JsonResponse
    {
        $this->authorize('incidents.view');

        $incidents = $this->service->getActive();

        return $this->successResponse(
            data: IncidentResource::collection($incidents),
            message: 'Incidentes activos obtenidos exitosamente'
        );
    }

    /**
     * Obtener incidentes reportados por el usuario autenticado
     */
    public function myIncidents(Request $request): JsonResponse
    {
        $incidents = $this->service->getByReporter($request->user()->id);

        return $this->successResponse(
            data: IncidentResource::collection($incidents),
            message: 'Mis incidentes obtenidos exitosamente'
        );
    }

    /**
     * Obtener incidentes asignados al usuario autenticado
     */
    public function assignedToMe(Request $request): JsonResponse
    {
        $this->authorize('incidents.resolve');

        $incidents = $this->service->getByAssignee($request->user()->id);

        return $this->successResponse(
            data: IncidentResource::collection($incidents),
            message: 'Incidentes asignados obtenidos exitosamente'
        );
    }

    /**
     * Obtener incidentes sin asignar
     */
    public function unassigned(): JsonResponse
    {
        $this->authorize('incidents.resolve');

        $incidents = $this->service->getUnassigned();

        return $this->successResponse(
            data: IncidentResource::collection($incidents),
            message: 'Incidentes sin asignar obtenidos exitosamente'
        );
    }

    /**
     * Obtener estadísticas de incidentes
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('incidents.view');

        $stats = $this->service->getStatistics();

        return $this->successResponse(
            data: $stats,
            message: 'Estadísticas obtenidas exitosamente'
        );
    }

    /**
     * Eliminar incidente
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('incidents.resolve');

        try {
            $this->service->delete($id);

            return $this->successResponse(
                message: 'Incidente eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
