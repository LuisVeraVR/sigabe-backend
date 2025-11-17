<?php

declare(strict_types=1);

namespace App\Domain\Loans\Models;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Loans\Enums\LoanStatus;
use App\Domain\Users\Models\User;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'equipment_id',
        'status',
        'requested_at',
        'approved_at',
        'approved_by',
        'expected_return_date',
        'actual_return_date',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => LoanStatus::class,
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    /**
     * Relación: Pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Pertenece a un equipo
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Relación: Aprobado por un usuario
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Solo préstamos activos (aprobados)
     */
    public function scopeActive($query)
    {
        return $query->where('status', LoanStatus::APPROVED);
    }

    /**
     * Scope: Solo préstamos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', LoanStatus::PENDING);
    }

    /**
     * Scope: Solo préstamos devueltos
     */
    public function scopeReturned($query)
    {
        return $query->where('status', LoanStatus::RETURNED);
    }

    /**
     * Scope: Solo préstamos vencidos
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', LoanStatus::OVERDUE);
    }

    /**
     * Scope: Por usuario
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Por equipo
     */
    public function scopeByEquipment($query, int $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * Verificar si el préstamo está vencido
     */
    public function isOverdue(): bool
    {
        return $this->status === LoanStatus::APPROVED &&
            $this->expected_return_date < now()->startOfDay();
    }

    /**
     * Verificar si puede ser devuelto
     */
    public function canBeReturned(): bool
    {
        return $this->status === LoanStatus::APPROVED;
    }

    /**
     * Verificar si puede ser aprobado
     */
    public function canBeApproved(): bool
    {
        return $this->status === LoanStatus::PENDING;
    }

    /**
     * Verificar si puede ser rechazado
     */
    public function canBeRejected(): bool
    {
        return $this->status === LoanStatus::PENDING;
    }

    /**
     * Factory
     */
    protected static function newFactory()
    {
        return LoanFactory::new();
    }
}
