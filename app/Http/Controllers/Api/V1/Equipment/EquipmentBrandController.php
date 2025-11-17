<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Equipment;

use App\Domain\Equipment\Models\EquipmentBrand;
use App\Http\Controllers\Controller;
use App\Http\Resources\Equipment\EquipmentBrandResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentBrandController extends Controller
{
    use ApiResponse;

    /**
     * Listar marcas de equipos
     */
    public function index(Request $request): JsonResponse
    {
        $query = EquipmentBrand::query();

        // Filtrar solo activas si se solicita
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Incluir conteos si se solicita
        if ($request->has('include_counts')) {
            $query->withCount('equipment');
        }

        $brands = $query->orderBy('name')->get();

        return $this->successResponse(
            data: EquipmentBrandResource::collection($brands),
            message: 'Marcas de equipos obtenidas exitosamente'
        );
    }

    /**
     * Ver detalle de una marca
     */
    public function show(int $id): JsonResponse
    {
        $brand = EquipmentBrand::find($id);

        if (!$brand) {
            return $this->notFoundResponse('Marca no encontrada');
        }

        return $this->successResponse(
            data: new EquipmentBrandResource($brand),
            message: 'Marca obtenida exitosamente'
        );
    }

    /**
     * Crear nueva marca
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:equipment_brands,name',
            'logo_url' => 'nullable|url|max:500',
            'website' => 'nullable|url|max:500',
            'country' => 'nullable|string|max:100',
        ]);

        $brand = EquipmentBrand::create($validated);

        return $this->createdResponse(
            data: new EquipmentBrandResource($brand),
            message: 'Marca creada exitosamente'
        );
    }

    /**
     * Actualizar marca
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $brand = EquipmentBrand::find($id);

        if (!$brand) {
            return $this->notFoundResponse('Marca no encontrada');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('equipment_brands')->ignore($id)],
            'logo_url' => 'nullable|url|max:500',
            'website' => 'nullable|url|max:500',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $brand->update($validated);

        return $this->successResponse(
            data: new EquipmentBrandResource($brand),
            message: 'Marca actualizada exitosamente'
        );
    }

    /**
     * Eliminar marca
     */
    public function destroy(int $id): JsonResponse
    {
        $brand = EquipmentBrand::find($id);

        if (!$brand) {
            return $this->notFoundResponse('Marca no encontrada');
        }

        // Verificar que no tenga equipos asociados
        if ($brand->equipment()->count() > 0) {
            return $this->errorResponse(
                'No se puede eliminar una marca que tiene equipos asociados',
                400
            );
        }

        $brand->delete();

        return $this->successResponse(
            message: 'Marca eliminada exitosamente'
        );
    }
}
