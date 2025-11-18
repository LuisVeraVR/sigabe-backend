<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class CancelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Users can cancel their own reservations or admins can cancel any
        $reservation = $this->route('id');

        return $this->user()->can('reservations.create');
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'El motivo de cancelación es requerido',
            'cancellation_reason.max' => 'El motivo de cancelación no puede exceder 1000 caracteres',
        ];
    }
}
