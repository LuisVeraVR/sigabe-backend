<?php

declare(strict_types=1);

namespace App\Domain\Spaces\Models;

use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;
use Database\Factories\SpaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'building',
        'floor',
        'location_description',
        'capacity',
        'space_type',
        'status',
        'description',
    ];

    protected $casts = [
        'space_type' => SpaceType::class,
        'status' => SpaceStatus::class,
        'capacity' => 'integer',
    ];

    protected static function newFactory()
    {
        return SpaceFactory::new();
    }

    // ==================== Scopes ====================

    public function scopeAvailable($query)
    {
        return $query->where('status', SpaceStatus::AVAILABLE);
    }

    public function scopeUnavailable($query)
    {
        return $query->where('status', SpaceStatus::UNAVAILABLE);
    }

    public function scopeInMaintenance($query)
    {
        return $query->where('status', SpaceStatus::MAINTENANCE);
    }

    public function scopeReserved($query)
    {
        return $query->where('status', SpaceStatus::RESERVED);
    }

    public function scopeByType($query, SpaceType $type)
    {
        return $query->where('space_type', $type);
    }

    public function scopeByBuilding($query, string $building)
    {
        return $query->where('building', $building);
    }

    public function scopeByFloor($query, string $floor)
    {
        return $query->where('floor', $floor);
    }

    public function scopeWithCapacity($query, int $minimumCapacity)
    {
        return $query->where('capacity', '>=', $minimumCapacity);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('building', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // ==================== Business Logic ====================

    public function isAvailable(): bool
    {
        return $this->status === SpaceStatus::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->status === SpaceStatus::RESERVED;
    }

    public function isInMaintenance(): bool
    {
        return $this->status === SpaceStatus::MAINTENANCE;
    }

    public function isUnavailable(): bool
    {
        return $this->status === SpaceStatus::UNAVAILABLE;
    }

    public function canBeReserved(): bool
    {
        return $this->status === SpaceStatus::AVAILABLE;
    }

    public function canBeModified(): bool
    {
        return !$this->isReserved();
    }

    public function getFullLocation(): string
    {
        $parts = array_filter([
            $this->building,
            $this->floor ? "Piso {$this->floor}" : null,
            $this->location_description,
        ]);

        return !empty($parts) ? implode(' - ', $parts) : 'Sin ubicación especificada';
    }

    public function hasCapacity(int $requiredCapacity): bool
    {
        if ($this->capacity === null) {
            return true; // Si no tiene capacidad definida, asumimos que es suficiente
        }

        return $this->capacity >= $requiredCapacity;
    }
}
