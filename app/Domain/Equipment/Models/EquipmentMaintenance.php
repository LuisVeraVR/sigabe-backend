<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Models;

use App\Domain\Equipment\Enums\MaintenancePriority;
use App\Domain\Equipment\Enums\MaintenanceStatus;
use App\Domain\Equipment\Enums\MaintenanceType;
use App\Domain\Users\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\EquipmentMaintenanceFactory;

class EquipmentMaintenance extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'equipment_id',
        'performed_by_user_id',
        'maintenance_type',
        'title',
        'description',
        'actions_taken',
        'scheduled_date',
        'start_date',
        'completion_date',
        'next_maintenance_date',
        'cost',
        'parts_replaced',
        'status',
        'priority',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'start_date' => 'datetime',
        'completion_date' => 'datetime',
        'next_maintenance_date' => 'date',
        'cost' => 'decimal:2',
        'parts_replaced' => 'array',
        'maintenance_type' => MaintenanceType::class,
        'status' => MaintenanceStatus::class,
        'priority' => MaintenancePriority::class,
    ];

    protected $appends = [
        'is_overdue',
        'days_until_scheduled',
    ];

    /**
     * Relación: Pertenece a un equipo
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Relación: Realizado por un usuario (técnico)
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * Scope: Solo mantenimientos programados
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', MaintenanceStatus::SCHEDULED);
    }

    /**
     * Scope: Mantenimientos pendientes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            MaintenanceStatus::SCHEDULED,
            MaintenanceStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Scope: Mantenimientos completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', MaintenanceStatus::COMPLETED);
    }

    /**
     * Scope: Mantenimientos vencidos
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', MaintenanceStatus::SCHEDULED)
            ->where('scheduled_date', '<', now());
    }

    /**
     * Scope: Próximos mantenimientos (N días)
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', MaintenanceStatus::SCHEDULED)
            ->whereBetween('scheduled_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Por tipo de mantenimiento
     */
    public function scopeOfType($query, MaintenanceType $type)
    {
        return $query->where('maintenance_type', $type);
    }

    /**
     * Scope: Por prioridad
     */
    public function scopeOfPriority($query, MaintenancePriority $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Accessor: Está vencido
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === MaintenanceStatus::SCHEDULED &&
            $this->scheduled_date < now();
    }

    /**
     * Accessor: Días hasta la fecha programada
     */
    public function getDaysUntilScheduledAttribute(): ?int
    {
        if ($this->status !== MaintenanceStatus::SCHEDULED) {
            return null;
        }

        return now()->diffInDays($this->scheduled_date, false);
    }

    /**
     * Accessor: Duración del mantenimiento (en horas)
     */
    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->start_date || !$this->completion_date) {
            return null;
        }

        return $this->start_date->diffInHours($this->completion_date, true);
    }

    /**
     * Iniciar mantenimiento
     */
    public function start(): void
    {
        $this->update([
            'status' => MaintenanceStatus::IN_PROGRESS,
            'start_date' => now(),
        ]);

        // Cambiar estado del equipo a mantenimiento
        $this->equipment->changeStatus(
            \App\Domain\Equipment\Enums\EquipmentStatus::MAINTENANCE,
            "Mantenimiento iniciado: {$this->title}"
        );
    }

    /**
     * Completar mantenimiento
     */
    public function complete(string $actionsTaken, ?float $cost = null): void
    {
        $this->update([
            'status' => MaintenanceStatus::COMPLETED,
            'completion_date' => now(),
            'actions_taken' => $actionsTaken,
            'cost' => $cost,
        ]);

        // Calcular próxima fecha de mantenimiento si es preventivo
        if ($this->maintenance_type === MaintenanceType::PREVENTIVE) {
            $this->calculateNextMaintenanceDate();
        }

        // Cambiar estado del equipo a disponible
        $this->equipment->changeStatus(
            \App\Domain\Equipment\Enums\EquipmentStatus::AVAILABLE,
            "Mantenimiento completado: {$this->title}"
        );
    }

    /**
     * Cancelar mantenimiento
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => MaintenanceStatus::CANCELLED,
            'actions_taken' => "Cancelado: {$reason}",
        ]);
    }

    /**
     * Calcular próxima fecha de mantenimiento
     */
    private function calculateNextMaintenanceDate(): void
    {
        $intervalDays = config('sigabe.equipment.maintenance.preventive_interval_days', 90);

        $this->update([
            'next_maintenance_date' => now()->addDays($intervalDays),
        ]);

        // Crear siguiente mantenimiento automáticamente
        self::create([
            'equipment_id' => $this->equipment_id,
            'maintenance_type' => MaintenanceType::PREVENTIVE,
            'title' => "Mantenimiento Preventivo - {$this->equipment->name}",
            'description' => 'Mantenimiento preventivo programado automáticamente',
            'scheduled_date' => now()->addDays($intervalDays),
            'status' => MaintenanceStatus::SCHEDULED,
            'priority' => MaintenancePriority::MEDIUM,
        ]);
    }
    protected static function newFactory()
    {
        return EquipmentMaintenanceFactory::new();
    }
}
