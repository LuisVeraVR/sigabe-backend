<?php

declare(strict_types=1);

namespace App\Http\Resources\Equipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentMaintenanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Equipo
            'equipment' => $this->whenLoaded('equipment', function () {
                return [
                    'id' => $this->equipment->id,
                    'name' => $this->equipment->name,
                    'asset_code' => $this->equipment->asset_code,
                    'type' => $this->equipment->type?->name,
                ];
            }),

            // Tipo de mantenimiento
            'maintenance_type' => [
                'value' => $this->maintenance_type->value,
                'label' => $this->maintenance_type->label(),
                'description' => $this->maintenance_type->description(),
            ],

            // Información
            'title' => $this->title,
            'description' => $this->description,
            'actions_taken' => $this->actions_taken,

            // Fechas
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'start_date' => $this->start_date?->toIso8601String(),
            'completion_date' => $this->completion_date?->toIso8601String(),
            'next_maintenance_date' => $this->next_maintenance_date?->toDateString(),

            // Costos
            'cost' => $this->cost,
            'parts_replaced' => $this->parts_replaced,

            // Estado
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'priority' => [
                'value' => $this->priority->value,
                'label' => $this->priority->label(),
                'color' => $this->priority->color(),
            ],

            // Técnico
            'performed_by' => $this->whenLoaded('performedBy', function () {
                return $this->performedBy ? [
                    'id' => $this->performedBy->id,
                    'full_name' => $this->performedBy->full_name,
                    'email' => $this->performedBy->email,
                ] : null;
            }),

            // Información adicional
            'is_overdue' => $this->is_overdue,
            'days_until_scheduled' => $this->days_until_scheduled,
            'duration_hours' => $this->duration_hours,

            // Fechas
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
