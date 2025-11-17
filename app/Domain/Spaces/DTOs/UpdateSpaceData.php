<?php

declare(strict_types=1);

namespace App\Domain\Spaces\DTOs;

use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;

readonly class UpdateSpaceData
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $building = null,
        public ?string $floor = null,
        public ?string $locationDescription = null,
        public ?int $capacity = null,
        public ?SpaceType $spaceType = null,
        public ?SpaceStatus $status = null,
        public ?string $description = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
            'building' => $this->building,
            'floor' => $this->floor,
            'location_description' => $this->locationDescription,
            'capacity' => $this->capacity,
            'space_type' => $this->spaceType,
            'status' => $this->status,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
