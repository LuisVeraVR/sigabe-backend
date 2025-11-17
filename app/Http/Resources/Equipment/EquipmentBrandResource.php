<?php

declare(strict_types=1);

namespace App\Http\Resources\Equipment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_url,
            'website' => $this->website,
            'country' => $this->country,
            'is_active' => $this->is_active,
            'equipment_count' => $this->when(
                $request->has('include_counts'),
                $this->equipment_count ?? 0
            ),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
