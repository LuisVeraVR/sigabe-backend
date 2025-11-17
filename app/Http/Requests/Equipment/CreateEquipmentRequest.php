<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use App\Domain\Equipment\Enums\EquipmentCondition;
use App\Domain\Equipment\Enums\EquipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipment.create');
    }

    public function rules(): array
    {
        return [
            'equipment_type_id' => [
                'required',
                'integer',
                Rule::exists('equipment_types', 'id')->where('is_active', true),
            ],
            'equipment_brand_id' => [
                'nullable',
                'integer',
                Rule::exists('equipment_brands', 'id')->where('is_active', true),
            ],
            'name' => [
                'required',
                'string',
                'max:200',
            ],
            'model' => [
                'nullable',
                'string',
                'max:100',
            ],
            'serial_number' => [
                'required',
                'string',
                'max:100',
                'unique:equipment,serial_number',
            ],
            'asset_code' => [
                'nullable',
                'string',
                'max:50',
                'unique:equipment,asset_code',
            ],
            'purchase_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'purchase_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'supplier' => [
                'nullable',
                'string',
                'max:200',
            ],
            'warranty_expiration_date' => [
                'nullable',
                'date',
                'after:purchase_date',
            ],
            'specifications' => [
                'nullable',
                'array',
            ],
            'specifications.*' => [
                'string',
                'max:500',
            ],
            'condition' => [
                'required',
                'string',
                Rule::enum(EquipmentCondition::class),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::enum(EquipmentStatus::class),
            ],
            'current_space_id' => [
                'nullable',
                'integer',
                Rule::exists('spaces', 'id'),
            ],
            'requires_accessories' => [
                'nullable',
                'array',
            ],
            'requires_accessories.*' => [
                'string',
                'max:100',
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
            'equipment_type_id.required' => 'El tipo de equipo es obligatorio.',
            'equipment_type_id.exists' => 'El tipo de equipo seleccionado no existe o está inactivo.',
            'equipment_brand_id.exists' => 'La marca seleccionada no existe o está inactiva.',
            'name.required' => 'El nombre del equipo es obligatorio.',
            'name.max' => 'El nombre no puede exceder 200 caracteres.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.unique' => 'Este número de serie ya está registrado.',
            'asset_code.unique' => 'Este código de activo ya está registrado.',
            'purchase_date.before_or_equal' => 'La fecha de compra no puede ser futura.',
            'warranty_expiration_date.after' => 'La fecha de vencimiento de garantía debe ser posterior a la fecha de compra.',
            'condition.required' => 'La condición del equipo es obligatoria.',
            'purchase_cost.min' => 'El costo debe ser mayor o igual a 0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => ucwords(strtolower(trim($this->name ?? ''))),
            'model' => strtoupper(trim($this->model ?? '')),
            'serial_number' => strtoupper(trim($this->serial_number ?? '')),
            'asset_code' => strtoupper(trim($this->asset_code ?? '')),
            'notes' => strip_tags($this->notes ?? ''),
        ]);
    }
}
