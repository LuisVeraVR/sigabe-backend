<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use App\Domain\Equipment\Enums\EquipmentCondition;
use App\Domain\Equipment\Enums\EquipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipment.edit');
    }

    public function rules(): array
    {
        $equipmentId = $this->route('equipment');

        return [
            'equipment_type_id' => [
                'sometimes',
                'integer',
                Rule::exists('equipment_types', 'id')->where('is_active', true),
            ],
            'equipment_brand_id' => [
                'nullable',
                'integer',
                Rule::exists('equipment_brands', 'id')->where('is_active', true),
            ],
            'name' => [
                'sometimes',
                'string',
                'max:200',
            ],
            'model' => [
                'nullable',
                'string',
                'max:100',
            ],
            'serial_number' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('equipment', 'serial_number')->ignore($equipmentId),
            ],
            'asset_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('equipment', 'asset_code')->ignore($equipmentId),
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
            ],
            'specifications' => [
                'nullable',
                'array',
            ],
            'condition' => [
                'sometimes',
                'string',
                Rule::enum(EquipmentCondition::class),
            ],
            'status' => [
                'sometimes',
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
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => ucwords(strtolower(trim($this->name)))]);
        }
        if ($this->has('model')) {
            $this->merge(['model' => strtoupper(trim($this->model))]);
        }
        if ($this->has('serial_number')) {
            $this->merge(['serial_number' => strtoupper(trim($this->serial_number))]);
        }
        if ($this->has('notes')) {
            $this->merge(['notes' => strip_tags($this->notes)]);
        }
    }
}
