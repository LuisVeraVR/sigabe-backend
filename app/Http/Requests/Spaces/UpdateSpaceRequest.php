<?php

declare(strict_types=1);

namespace App\Http\Requests\Spaces;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('spaces.edit');
    }

    public function rules(): array
    {
        $spaceId = $this->route('id');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('spaces', 'code')->ignore($spaceId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'space_type' => [
                'sometimes',
                'string',
                Rule::in([
                    'classroom',
                    'lab',
                    'auditorium',
                    'meeting_room',
                    'library',
                    'storage',
                    'other',
                ]),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['available', 'unavailable', 'maintenance', 'reserved']),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio',
            'code.unique' => 'El código ya existe',
            'name.required' => 'El nombre es obligatorio',
            'capacity.integer' => 'La capacidad debe ser un número entero',
            'capacity.min' => 'La capacidad debe ser al menos 1',
            'space_type.in' => 'El tipo de espacio no es válido',
            'status.in' => 'El estado no es válido',
        ];
    }
}
