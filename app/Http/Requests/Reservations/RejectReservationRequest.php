<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class RejectReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reservations.approve');
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'El motivo de rechazo es requerido',
            'rejection_reason.max' => 'El motivo de rechazo no puede exceder 1000 caracteres',
        ];
    }
}
