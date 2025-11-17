<?php

declare(strict_types=1);

namespace App\Domain\Reservations\Models;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Loans\Models\Loan;
use App\Domain\Reservations\Enums\ReservationStatus;
use App\Domain\Users\Models\User;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'equipment_id',
        'status',
        'start_date',
        'end_date',
        'approved_at',
        'approved_by',
        'notes',
        'cancellation_reason',
        'rejection_reason',
        'converted_loan_id',
    ];

    protected $casts = [
        'status' => ReservationStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function convertedLoan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'converted_loan_id');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', ReservationStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ReservationStatus::APPROVED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ReservationStatus::ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ReservationStatus::COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', ReservationStatus::CANCELLED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ReservationStatus::REJECTED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', ReservationStatus::EXPIRED);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEquipment($query, int $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    // Business Logic Methods

    public function isExpired(): bool
    {
        return $this->status === ReservationStatus::APPROVED
            && now()->isAfter($this->start_date);
    }

    public function canBeActivated(): bool
    {
        return $this->status === ReservationStatus::APPROVED
            && now()->isSameDay($this->start_date);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            ReservationStatus::PENDING,
            ReservationStatus::APPROVED,
        ]);
    }

    public function canBeCompleted(): bool
    {
        return $this->status === ReservationStatus::ACTIVE;
    }

    public function canBeConvertedToLoan(): bool
    {
        return $this->status === ReservationStatus::APPROVED
            && now()->isSameDay($this->start_date)
            && is_null($this->converted_loan_id);
    }

    public function isConverted(): bool
    {
        return !is_null($this->converted_loan_id);
    }

    public function getDurationInDays(): int
    {
        return (int) ($this->start_date->diffInDays($this->end_date) + 1);
    }
}
