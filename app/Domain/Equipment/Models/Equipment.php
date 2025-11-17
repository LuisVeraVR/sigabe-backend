<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Models;

use App\Domain\Equipment\Enums\EquipmentCondition;
use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Traits\Auditable;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'equipment';


    protected $fillable = [
        'equipment_type_id',
        'equipment_brand_id',
        'name',
        'model',
        'serial_number',
        'asset_code',
        'purchase_date',
        'purchase_cost',
        'supplier',
        'warranty_expiration_date',
        'specifications',
        'condition',
        'status',
        'current_space_id',
        'requires_accessories',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'warranty_expiration_date' => 'date',
        'specifications' => 'array',
        'requires_accessories' => 'array',
        'condition' => EquipmentCondition::class,
        'status' => EquipmentStatus::class,
    ];

    protected $appends = [
        'is_available',
        'is_under_warranty',
    ];

    /**
     * Relación: Pertenece a un tipo de equipo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    /**
     * Relación: Pertenece a una marca
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(EquipmentBrand::class, 'equipment_brand_id');
    }

    /**
     * Relación: Puede estar en un espacio
     */
    public function currentSpace(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Spaces\Models\Space::class, 'current_space_id');
    }

    /**
     * Relación: Tiene muchos mantenimientos
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }

    /**
     * Relación: Tiene muchos préstamos (polimórfica)
     */
    public function loans(): HasMany
    {
        return $this->hasMany(\App\Domain\Loans\Models\Loan::class, 'loanable_id')
            ->where('loanable_type', self::class);
    }

    /**
     * Relación: Tiene muchos incidentes (polimórfica)
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(\App\Domain\Incidents\Models\Incident::class, 'incidentable_id')
            ->where('incidentable_type', self::class);
    }

    /**
     * Scope: Solo equipos disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', EquipmentStatus::AVAILABLE);
    }

    /**
     * Scope: Solo equipos activos (no retirados)
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', EquipmentStatus::RETIRED);
    }

    /**
     * Scope: Por tipo de equipo
     */
    public function scopeOfType($query, int $typeId)
    {
        return $query->where('equipment_type_id', $typeId);
    }

    /**
     * Scope: Por marca
     */
    public function scopeOfBrand($query, int $brandId)
    {
        return $query->where('equipment_brand_id', $brandId);
    }

    /**
     * Scope: Con garantía vigente
     */
    public function scopeUnderWarranty($query)
    {
        return $query->whereNotNull('warranty_expiration_date')
            ->where('warranty_expiration_date', '>=', now());
    }

    /**
     * Scope: Búsqueda general
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('asset_code', 'like', "%{$search}%");
        });
    }

    /**
     * Accessor: Está disponible
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === EquipmentStatus::AVAILABLE;
    }

    /**
     * Accessor: Está bajo garantía
     */
    public function getIsUnderWarrantyAttribute(): bool
    {
        return $this->warranty_expiration_date &&
            $this->warranty_expiration_date >= now();
    }

    /**
     * Accessor: Días hasta el próximo mantenimiento
     */
    public function getDaysUntilMaintenanceAttribute(): ?int
    {
        $nextMaintenance = $this->maintenances()
            ->where('status', 'scheduled')
            ->whereDate('scheduled_date', '>=', now())
            ->orderBy('scheduled_date', 'asc')
            ->first();

        if (!$nextMaintenance) {
            return null;
        }

        return now()->diffInDays($nextMaintenance->scheduled_date);
    }

    /**
     * Verificar si puede ser prestado
     */
    public function canBeLent(): bool
    {
        return $this->status->canBeLent() &&
            $this->condition !== EquipmentCondition::POOR;
    }

    /**
     * Cambiar estado del equipo
     */
    public function changeStatus(EquipmentStatus $newStatus, ?string $reason = null): void
    {
        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        // Auditar cambio de estado
        \App\Domain\Shared\Models\AuditLog::log(
            action: 'status_changed',
            module: 'equipment',
            description: "Estado cambiado de {$oldStatus->label()} a {$newStatus->label()}" .
                ($reason ? ": {$reason}" : ''),
            record: $this,
            changes: [
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Generar código único de activo
     */
    public static function generateAssetCode(): string
    {
        $year = now()->year;
        $lastEquipment = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastEquipment ? (int) substr($lastEquipment->asset_code, -4) + 1 : 1;

        return "EQ-{$year}-" . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
    protected static function newFactory()
    {
        return EquipmentFactory::new();
    }
}
