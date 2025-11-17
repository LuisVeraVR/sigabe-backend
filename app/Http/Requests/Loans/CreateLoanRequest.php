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
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('status', 'active'),
            ],
            'loanable_type' => [
                'required',
                'string',
                Rule::in(['equipment', 'catalog']),
            ],
            'loanable_id' => [
                'required',
                'integer',
            ],
            'expected_return_date' => [
                'required',
                'date',
                'after:today',
                'before:' . now()->addDays(config('sigabe.loans.default_duration_days') + 1)->toDateString(),
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
            'expected_return_date.after' => 'La fecha de devolución debe ser posterior a hoy.',
            'expected_return_date.before' => 'La fecha de devolución no puede exceder ' . config('sigabe.loans.default_duration_days') . ' días.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => strip_tags($this->notes ?? ''),
        ]);
    }
}
