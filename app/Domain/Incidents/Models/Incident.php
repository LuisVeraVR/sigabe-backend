<?php

declare(strict_types=1);

namespace App\Domain\Incidents\Models;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Incidents\Enums\IncidentPriority;
use App\Domain\Incidents\Enums\IncidentStatus;
use App\Domain\Users\Models\User;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'reported_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'resolution_notes',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => IncidentStatus::class,
        'priority' => IncidentPriority::class,
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes

    public function scopeReportado($query)
    {
        return $query->where('status', IncidentStatus::REPORTADO);
    }

    public function scopeEnRevision($query)
    {
        return $query->where('status', IncidentStatus::EN_REVISION);
    }

    public function scopeEnReparacion($query)
    {
        return $query->where('status', IncidentStatus::EN_REPARACION);
    }

    public function scopeResuelto($query)
    {
        return $query->where('status', IncidentStatus::RESUELTO);
    }

    public function scopeCerrado($query)
    {
        return $query->where('status', IncidentStatus::CERRADO);
    }

    public function scopeByPriority($query, IncidentPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByEquipment($query, int $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeByReporter($query, int $userId)
    {
        return $query->where('reported_by', $userId);
    }

    public function scopeByAssignee($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            IncidentStatus::REPORTADO,
            IncidentStatus::EN_REVISION,
            IncidentStatus::EN_REPARACION,
        ]);
    }

    public function scopeOrderByPriority($query, string $direction = 'desc')
    {
        // Ordenar por prioridad usando el campo enum
        return $query->orderByRaw(
            "CASE priority
                WHEN 'critica' THEN 4
                WHEN 'alta' THEN 3
                WHEN 'media' THEN 2
                WHEN 'baja' THEN 1
                ELSE 0
            END {$direction}"
        );
    }

    // Business Logic Methods

    public function isOpen(): bool
    {
        return in_array($this->status, [
            IncidentStatus::REPORTADO,
            IncidentStatus::EN_REVISION,
            IncidentStatus::EN_REPARACION,
        ]);
    }

    public function isClosed(): bool
    {
        return $this->status === IncidentStatus::CERRADO;
    }

    public function isResolved(): bool
    {
        return $this->status === IncidentStatus::RESUELTO;
    }

    public function canBeAssigned(): bool
    {
        return in_array($this->status, [
            IncidentStatus::REPORTADO,
            IncidentStatus::EN_REVISION,
        ]);
    }

    public function canBeResolved(): bool
    {
        return in_array($this->status, [
            IncidentStatus::EN_REVISION,
            IncidentStatus::EN_REPARACION,
        ]);
    }

    public function canBeClosed(): bool
    {
        return $this->status === IncidentStatus::RESUELTO;
    }

    public function canBeReopened(): bool
    {
        return $this->status === IncidentStatus::CERRADO;
    }

    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    public function getResolutionTimeInHours(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }

        return (int) $this->created_at->diffInHours($this->resolved_at);
    }

    /**
     * Factory
     */
    protected static function newFactory()
    {
        return IncidentFactory::new();
    }
}
