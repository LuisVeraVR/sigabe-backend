<?php

declare(strict_types=1);

namespace App\Http\Resources\Reservations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Usuario solicitante
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            // Equipo reservado
            'equipment' => $this->whenLoaded('equipment', function () {
                return [
                    'id' => $this->equipment->id,
                    'name' => $this->equipment->name,
                    'serial_number' => $this->equipment->serial_number,
                    'asset_code' => $this->equipment->asset_code,
                ];
            }),

            // Estado de la reserva
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            // Fechas
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'approved_at' => $this->approved_at?->toIso8601String(),

            // Usuario que aprobó/rechazó
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return $this->approvedBy ? [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ] : null;
            }),

            // Notas y razones
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'rejection_reason' => $this->rejection_reason,

            // Préstamo convertido
            'converted_loan' => $this->whenLoaded('convertedLoan', function () {
                return $this->convertedLoan ? [
                    'id' => $this->convertedLoan->id,
                    'status' => $this->convertedLoan->status->value,
                ] : null;
            }),

            // Información adicional
            'is_expired' => $this->isExpired(),
            'can_be_activated' => $this->canBeActivated(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_completed' => $this->canBeCompleted(),
            'can_be_converted_to_loan' => $this->canBeConvertedToLoan(),
            'is_converted' => $this->isConverted(),
            'duration_in_days' => $this->getDurationInDays(),

            // Fechas del sistema
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
