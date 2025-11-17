<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Spaces;

use App\Domain\Spaces\DTOs\CreateSpaceData;
use App\Domain\Spaces\DTOs\UpdateSpaceData;
use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;
use App\Domain\Spaces\Services\SpaceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Spaces\CreateSpaceRequest;
use App\Http\Requests\Spaces\UpdateSpaceRequest;
use App\Http\Resources\Spaces\SpaceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function __construct(
        private readonly SpaceService $service
    ) {}

    /**
     * Listar espacios con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('spaces.view');

        $filters = $request->only([
            'search',
            'space_type',
            'status',
            'building',
            'floor',
            'min_capacity',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $spaces = $this->service->paginate($filters, $perPage);

        return $this->successResponse(
            data: SpaceResource::collection($spaces),
            message: 'Espacios obtenidos exitosamente',
            meta: [
                'total' => $spaces->total(),
                'per_page' => $spaces->perPage(),
                'current_page' => $spaces->currentPage(),
                'last_page' => $spaces->lastPage(),
            ]
        );
    }

    /**
     * Ver detalles de un espacio
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('spaces.view');

        $space = $this->service->findById($id);

        if (!$space) {
            return $this->errorResponse(
                message: 'Espacio no encontrado',
                code: 404
            );
        }

        return $this->successResponse(
            data: new SpaceResource($space),
            message: 'Espacio obtenido exitosamente'
        );
    }

    /**
     * Crear un nuevo espacio
     */
    public function store(CreateSpaceRequest $request): JsonResponse
    {
        try {
            $data = new CreateSpaceData(
                code: $request->input('code'),
                name: $request->input('name'),
                building: $request->input('building'),
                floor: $request->input('floor'),
                locationDescription: $request->input('location_description'),
                capacity: $request->input('capacity'),
                spaceType: $request->input('space_type')
                    ? SpaceType::from($request->input('space_type'))
                    : SpaceType::CLASSROOM,
                description: $request->input('description'),
            );

            $space = $this->service->create($data);

            return $this->successResponse(
                data: new SpaceResource($space),
                message: 'Espacio creado exitosamente',
                code: 201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: 400
            );
        }
    }

    /**
     * Actualizar un espacio
     */
    public function update(UpdateSpaceRequest $request, int $id): JsonResponse
    {
        try {
            $data = new UpdateSpaceData(
                code: $request->input('code'),
                name: $request->input('name'),
                building: $request->input('building'),
                floor: $request->input('floor'),
                locationDescription: $request->input('location_description'),
                capacity: $request->input('capacity'),
                spaceType: $request->filled('space_type')
                    ? SpaceType::from($request->input('space_type'))
                    : null,
                status: $request->filled('status')
                    ? SpaceStatus::from($request->input('status'))
                    : null,
                description: $request->input('description'),
            );

            $space = $this->service->update($id, $data);

            return $this->successResponse(
                data: new SpaceResource($space),
                message: 'Espacio actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: 400
            );
        }
    }

    /**
     * Eliminar un espacio
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('spaces.delete');

        try {
            $this->service->delete($id);

            return $this->successResponse(
                message: 'Espacio eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: 400
            );
        }
    }

    /**
     * Marcar espacio como disponible
     */
    public function markAsAvailable(int $id): JsonResponse
    {
        $this->authorize('spaces.edit');

        try {
            $space = $this->service->markAsAvailable($id);

            return $this->successResponse(
                data: new SpaceResource($space),
                message: 'Espacio marcado como disponible'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: 400
            );
        }
    }

    /**
     * Marcar espacio como no disponible
     */
    public function markAsUnavailable(int $id): JsonResponse
    {
        $this->authorize('spaces.edit');

        try {
            $space = $this->service->markAsUnavailable($id);

            return $this->successResponse(
                data: new SpaceResource($space),
                message: 'Espacio marcado como no disponible'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: 400
            );
        }
    }

    /**
     * Marcar espacio en mantenimiento
     */
    public function markAsInMaintenance(int $id): JsonResponse
    {
        $this->authorize('spaces.edit');

        try {
            $space = $this->service->markAsInMaintenance($id);

            return $this->successResponse(
                data: new SpaceResource($space),
                message: 'Espacio marcado en mantenimiento'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: 400
            );
        }
    }

    /**
     * Listar espacios disponibles
     */
    public function available(): JsonResponse
    {
        $this->authorize('spaces.view');

        $spaces = $this->service->getAvailable();

        return $this->successResponse(
            data: SpaceResource::collection($spaces),
            message: 'Espacios disponibles obtenidos exitosamente'
        );
    }

    /**
     * Obtener estadísticas de espacios
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('spaces.view');

        $stats = $this->service->getStatistics();

        return $this->successResponse(
            data: $stats,
            message: 'Estadísticas obtenidas exitosamente'
        );
    }

    /**
     * Obtener lista de edificios
     */
    public function buildings(): JsonResponse
    {
        $this->authorize('spaces.view');

        $buildings = $this->service->getBuildings();

        return $this->successResponse(
            data: $buildings,
            message: 'Edificios obtenidos exitosamente'
        );
    }

    /**
     * Obtener lista de pisos
     */
    public function floors(): JsonResponse
    {
        $this->authorize('spaces.view');

        $floors = $this->service->getFloors();

        return $this->successResponse(
            data: $floors,
            message: 'Pisos obtenidos exitosamente'
        );
    }
}
