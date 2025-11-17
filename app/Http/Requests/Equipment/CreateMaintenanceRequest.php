<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use App\Domain\Equipment\Enums\MaintenancePriority;
use App\Domain\Equipment\Enums\MaintenanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipment.maintenance') ||
               $this->user()->can('equipment.create');
    }

    public function rules(): array
    {
        return [
            'equipment_id' => [
                'required',
                'integer',
                Rule::exists('equipment', 'id'),
            ],
            'maintenance_type' => [
                'required',
                'string',
                Rule::enum(MaintenanceType::class),
            ],
            'title' => [
                'required',
                'string',
                'max:200',
            ],
            'description' => [
                'required',
                'string',
                'max:2000',
            ],
            'scheduled_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'priority' => [
                'nullable',
                'string',
                Rule::enum(MaintenancePriority::class),
            ],
            'performed_by_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('status', 'active'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => 'El equipo es obligatorio.',
            'equipment_id.exists' => 'El equipo seleccionado no existe.',
            'maintenance_type.required' => 'El tipo de mantenimiento es obligatorio.',
            'title.required' => 'El título es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'scheduled_date.required' => 'La fecha programada es obligatoria.',
            'scheduled_date.after_or_equal' => 'La fecha programada no puede ser en el pasado.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => ucfirst(trim($this->title ?? '')),
            'description' => strip_tags($this->description ?? ''),
            'performed_by_user_id' => $this->performed_by_user_id ?? auth()->id(),
        ]);
    }
}
