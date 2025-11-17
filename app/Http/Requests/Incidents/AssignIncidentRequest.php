<?php

declare(strict_types=1);

namespace App\Http\Requests\Incidents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('incidents.resolve');
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'El técnico asignado es requerido',
            'assigned_to.exists' => 'El usuario seleccionado no existe',
        ];
    }
}
