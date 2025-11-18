<?php

declare(strict_types=1);

namespace App\Http\Requests\Spaces;

use App\Domain\Spaces\Enums\SpaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('spaces.create');
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('spaces', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'space_type' => [
                'nullable',
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
        ];
    }
}
