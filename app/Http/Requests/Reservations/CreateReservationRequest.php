<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reservations.create');
    }

    public function rules(): array
    {
        return [
            'equipment_id' => [
                'required',
                'integer',
                Rule::exists('equipment', 'id')->whereNull('deleted_at'),
            ],
            'start_date' => [
                'required',
                'date',
                'after:today',
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => 'El equipo es requerido',
            'equipment_id.exists' => 'El equipo seleccionado no existe',
            'start_date.required' => 'La fecha de inicio es requerida',
            'start_date.after' => 'La fecha de inicio debe ser posterior a hoy',
            'end_date.required' => 'La fecha de fin es requerida',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres',
        ];
    }
}
