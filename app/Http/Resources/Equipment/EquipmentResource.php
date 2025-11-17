<?php

declare(strict_types=1);

namespace App\Http\Resources\Equipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'asset_code' => $this->asset_code,

            // Tipo y marca
            'type' => $this->whenLoaded('type', function () {
                return [
                    'id' => $this->type->id,
                    'name' => $this->type->name,
                    'slug' => $this->type->slug,
                    'icon' => $this->type->icon,
                    'requires_training' => $this->type->requires_training,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return $this->brand ? [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                    'country' => $this->brand->country,
                ] : null;
            }),

            // Información de compra (solo para admin/colaborador)
            'purchase_date' => $this->when(
                $request->user()?->can('equipment.view_full'),
                $this->purchase_date?->toDateString()
            ),
            'purchase_cost' => $this->when(
                $request->user()?->can('equipment.view_full'),
                $this->purchase_cost
            ),
            'supplier' => $this->when(
                $request->user()?->can('equipment.view_full'),
                $this->supplier
            ),

            // Garantía
            'warranty_expiration_date' => $this->warranty_expiration_date?->toDateString(),
            'is_under_warranty' => $this->is_under_warranty,

            // Especificaciones
            'specifications' => $this->specifications,

            // Estado y condición
            'condition' => [
                'value' => $this->condition->value,
                'label' => $this->condition->label(),
                'color' => $this->condition->color(),
            ],
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            // Ubicación actual
            'current_space' => $this->whenLoaded('currentSpace', function () {
                return $this->currentSpace ? [
                    'id' => $this->currentSpace->id,
                    'name' => $this->currentSpace->name,
                    'code' => $this->currentSpace->code,
                ] : null;
            }),

            // Accesorios
            'requires_accessories' => $this->requires_accessories,

            // Disponibilidad
            'is_available' => $this->is_available,
            'can_be_lent' => $this->canBeLent(),

            // Mantenimiento
            'days_until_maintenance' => $this->days_until_maintenance,
            'latest_maintenances' => $this->whenLoaded('maintenances', function () {
                return EquipmentMaintenanceResource::collection($this->maintenances);
            }),

            // Notas
            'notes' => $this->notes,

            // Fechas
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
