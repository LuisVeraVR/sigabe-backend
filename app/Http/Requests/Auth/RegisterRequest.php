<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\Enums\DocumentType;
use App\Domain\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo ciertos roles pueden registrar usuarios
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/u', // Solo letras y espacios
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                Rule::when(
                    config('sigabe.security.require_institutional_email'),
                    ['regex:/^[\w\.-]+@sena\.edu\.co$/i']
                ),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(3), // Verifica en base de datos de contraseñas comprometidas
            ],
            'document_type' => [
                'required',
                'string',
                Rule::enum(DocumentType::class),
            ],
            'document_number' => [
                'required',
                'string',
                'max:50',
                'unique:users,document_number',
                'regex:/^[0-9]+$/', // Solo números
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]+$/', // Formato telefónico
            ],
            'role' => [
                'required',
                'string',
                Rule::in(UserRole::toArray()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'El nombre solo puede contener letras y espacios.',
            'last_name.regex' => 'El apellido solo puede contener letras y espacios.',
            'email.regex' => 'Debe usar un correo institucional @sena.edu.co',
            'email.email' => 'El correo electrónico debe ser válido y tener un dominio real.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.letters' => 'La contraseña debe contener al menos una letra.',
            'password.mixed' => 'La contraseña debe contener mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe contener al menos un número.',
            'password.symbols' => 'La contraseña debe contener al menos un símbolo (!@#$%^&*).',
            'password.uncompromised' => 'Esta contraseña ha sido comprometida en filtraciones de datos. Por favor, elija otra.',
            'document_number.regex' => 'El número de documento solo puede contener números.',
            'phone.regex' => 'El formato del teléfono no es válido.',
        ];
    }

    /**
     * Sanitizar datos antes de validación
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'first_name' => ucwords(strtolower(trim($this->first_name))),
            'last_name' => ucwords(strtolower(trim($this->last_name))),
            'document_number' => preg_replace('/\D/', '', $this->document_number ?? ''),
            'phone' => preg_replace('/[^\d+]/', '', $this->phone ?? ''),
        ]);
    }
}
