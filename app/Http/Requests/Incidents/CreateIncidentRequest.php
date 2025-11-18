<?php

declare(strict_types=1);

namespace App\Http\Requests\Incidents;

use App\Domain\Incidents\Enums\IncidentPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('incidents.create');
    }

    public function rules(): array
    {
        return [
            'equipment_id' => [
                'required',
                'integer',
                Rule::exists('equipment', 'id')->whereNull('deleted_at'),
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                'max:5000',
            ],
            'priority' => [
                'nullable',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, IncidentPriority::cases())),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => 'El equipo es requerido',
            'equipment_id.exists' => 'El equipo seleccionado no existe',
            'title.required' => 'El título es requerido',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'description.required' => 'La descripción es requerida',
            'description.max' => 'La descripción no puede exceder 5000 caracteres',
            'priority.in' => 'La prioridad seleccionada no es válida',
        ];
    }
}
