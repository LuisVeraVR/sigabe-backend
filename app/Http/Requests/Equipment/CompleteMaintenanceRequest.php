<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipment.maintenance') ||
               $this->user()->can('incidents.resolve');
    }

    public function rules(): array
    {
        return [
            'actions_taken' => [
                'required',
                'string',
                'max:2000',
            ],
            'cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'parts_replaced' => [
                'nullable',
                'array',
            ],
            'parts_replaced.*.name' => [
                'required',
                'string',
                'max:200',
            ],
            'parts_replaced.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'parts_replaced.*.cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'actions_taken.required' => 'Las acciones tomadas son obligatorias.',
            'cost.min' => 'El costo debe ser mayor o igual a 0.',
            'parts_replaced.*.name.required' => 'El nombre de la parte es obligatorio.',
            'parts_replaced.*.quantity.required' => 'La cantidad es obligatoria.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'actions_taken' => strip_tags($this->actions_taken ?? ''),
        ]);
    }
}
