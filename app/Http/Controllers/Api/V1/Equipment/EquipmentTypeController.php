<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Equipment;

use App\Domain\Equipment\Models\EquipmentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Equipment\EquipmentTypeResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentTypeController extends Controller
{
    use ApiResponse;

    /**
     * Listar tipos de equipos
     */
    public function index(Request $request): JsonResponse
    {
        $query = EquipmentType::query();

        // Filtrar solo activos si se solicita
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Incluir conteos si se solicita
        if ($request->has('include_counts')) {
            $query->withCount('equipment');
        }

        $types = $query->orderBy('name')->get();

        return $this->successResponse(
            data: EquipmentTypeResource::collection($types),
            message: 'Tipos de equipos obtenidos exitosamente'
        );
    }

    /**
     * Ver detalle de un tipo
     */
    public function show(int $id): JsonResponse
    {
        $type = EquipmentType::find($id);

        if (!$type) {
            return $this->notFoundResponse('Tipo de equipo no encontrado');
        }

        return $this->successResponse(
            data: new EquipmentTypeResource($type),
            message: 'Tipo de equipo obtenido exitosamente'
        );
    }

    /**
     * Crear nuevo tipo de equipo
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:equipment_types,name',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'requires_training' => 'boolean',
            'average_loan_duration_hours' => 'required|integer|min:1|max:720',
        ]);

        $type = EquipmentType::create($validated);

        return $this->createdResponse(
            data: new EquipmentTypeResource($type),
            message: 'Tipo de equipo creado exitosamente'
        );
    }

    /**
     * Actualizar tipo de equipo
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $type = EquipmentType::find($id);

        if (!$type) {
            return $this->notFoundResponse('Tipo de equipo no encontrado');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('equipment_types')->ignore($id)],
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'requires_training' => 'boolean',
            'average_loan_duration_hours' => 'sometimes|integer|min:1|max:720',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        return $this->successResponse(
            data: new EquipmentTypeResource($type),
            message: 'Tipo de equipo actualizado exitosamente'
        );
    }

    /**
     * Eliminar tipo de equipo
     */
    public function destroy(int $id): JsonResponse
    {
        $type = EquipmentType::find($id);

        if (!$type) {
            return $this->notFoundResponse('Tipo de equipo no encontrado');
        }

        // Verificar que no tenga equipos asociados
        if ($type->equipment()->count() > 0) {
            return $this->errorResponse(
                'No se puede eliminar un tipo que tiene equipos asociados',
                400
            );
        }

        $type->delete();

        return $this->successResponse(
            message: 'Tipo de equipo eliminado exitosamente'
        );
    }
}
