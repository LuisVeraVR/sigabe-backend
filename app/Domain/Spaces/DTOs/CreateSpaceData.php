<?php

declare(strict_types=1);

namespace App\Domain\Spaces\DTOs;

use App\Domain\Spaces\Enums\SpaceType;

readonly class CreateSpaceData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $building = null,
        public ?string $floor = null,
        public ?string $locationDescription = null,
        public ?int $capacity = null,
        public SpaceType $spaceType = SpaceType::CLASSROOM,
        public ?string $description = null,
    ) {}

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'building' => $this->building,
            'floor' => $this->floor,
            'location_description' => $this->locationDescription,
            'capacity' => $this->capacity,
            'space_type' => $this->spaceType,
            'description' => $this->description,
        ];
    }
}
