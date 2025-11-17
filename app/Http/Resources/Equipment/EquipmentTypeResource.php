<?php

declare(strict_types=1);

namespace App\Http\Resources\Equipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'requires_training' => $this->requires_training,
            'average_loan_duration_hours' => $this->average_loan_duration_hours,
            'is_active' => $this->is_active,
            'equipment_count' => $this->when(
                $request->has('include_counts'),
                $this->equipment_count ?? 0
            ),
            'available_equipment_count' => $this->when(
                $request->has('include_counts'),
                $this->available_equipment_count ?? 0
            ),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
