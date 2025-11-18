<?php

declare(strict_types=1);

namespace App\Http\Resources\Spaces;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'building' => $this->building,
            'floor' => $this->floor,
            'location_description' => $this->location_description,
            'full_location' => $this->getFullLocation(),
            'capacity' => $this->capacity,
            'space_type' => [
                'value' => $this->space_type->value,
                'label' => $this->space_type->label(),
                'icon' => $this->space_type->icon(),
            ],
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'description' => $this->description,
            'is_available' => $this->isAvailable(),
            'is_reserved' => $this->isReserved(),
            'is_in_maintenance' => $this->isInMaintenance(),
            'can_be_reserved' => $this->canBeReserved(),
            'can_be_modified' => $this->canBeModified(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
