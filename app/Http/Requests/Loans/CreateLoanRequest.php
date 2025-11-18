<?php

declare(strict_types=1);

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('loans.create');
    }

    public function rules(): array
    {
        return [
            'equipment_id' => [
                'required',
                'integer',
                Rule::exists('equipment', 'id'),
            ],
            'expected_return_date' => [
                'required',
                'date',
                'after:today',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => 'Debe seleccionar un equipo',
            'equipment_id.exists' => 'El equipo seleccionado no existe',
            'expected_return_date.required' => 'Debe especificar la fecha de devolución esperada',
            'expected_return_date.after' => 'La fecha de devolución debe ser futura',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => strip_tags($this->notes ?? ''),
            ]);
        }
    }
}
