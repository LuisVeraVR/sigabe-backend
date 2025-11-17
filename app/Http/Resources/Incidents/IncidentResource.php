<?php

declare(strict_types=1);

namespace App\Http\Resources\Incidents;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Equipo relacionado
            'equipment' => $this->whenLoaded('equipment', function () {
                return [
                    'id' => $this->equipment->id,
                    'name' => $this->equipment->name,
                    'serial_number' => $this->equipment->serial_number,
                    'asset_code' => $this->equipment->asset_code,
                ];
            }),

            // Usuario que reportó
            'reported_by' => $this->whenLoaded('reportedBy', function () {
                return [
                    'id' => $this->reportedBy->id,
                    'name' => $this->reportedBy->name,
                    'email' => $this->reportedBy->email,
                ];
            }),

            // Técnico asignado
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ] : null;
            }),

            // Información del incidente
            'title' => $this->title,
            'description' => $this->description,

            // Estado
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            // Prioridad
            'priority' => [
                'value' => $this->priority->value,
                'label' => $this->priority->label(),
                'color' => $this->priority->color(),
                'order' => $this->priority->order(),
            ],

            // Resolución
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),

            // Información adicional
            'is_open' => $this->isOpen(),
            'is_closed' => $this->isClosed(),
            'is_resolved' => $this->isResolved(),
            'is_assigned' => $this->isAssigned(),
            'can_be_assigned' => $this->canBeAssigned(),
            'can_be_resolved' => $this->canBeResolved(),
            'can_be_closed' => $this->canBeClosed(),
            'can_be_reopened' => $this->canBeReopened(),
            'resolution_time_in_hours' => $this->getResolutionTimeInHours(),

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
