<?php

declare(strict_types=1);

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;

class RejectLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('loans.approve');
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Debe especificar el motivo del rechazo',
            'rejection_reason.max' => 'El motivo no puede exceder 500 caracteres',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rejection_reason')) {
            $this->merge([
                'rejection_reason' => strip_tags($this->rejection_reason ?? ''),
            ]);
        }
    }
}
