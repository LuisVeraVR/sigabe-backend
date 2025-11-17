<?php

declare(strict_types=1);

namespace App\Http\Requests\Incidents;

use Illuminate\Foundation\Http\FormRequest;

class ResolveIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('incidents.resolve');
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Las notas de resolución son requeridas',
            'resolution_notes.max' => 'Las notas de resolución no pueden exceder 5000 caracteres',
        ];
    }
}
