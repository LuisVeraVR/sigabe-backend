<?php

declare(strict_types=1);

use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Models\User;
use Tests\Traits\WithRolesAndPermissions;
use function Pest\Laravel\postJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
uses(WithRolesAndPermissions::class);

describe('Input Validation', function () {

    beforeEach(function () {
        $this->seedRolesAndPermissions();

        // Crear admin para poder registrar usuarios
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN_PROGRAMADOR->value);
        $this->admin = $admin;
    });

    it('rejects weak passwords', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@sena.edu.co',
                'password' => '12345', // Contraseña débil
                'password_confirmation' => '12345',
                'document_type' => 'CC',
                'document_number' => '1234567890',
                'role' => 'usuario',
            ]);

        $response->assertStatus(422);
        expect($response->json('errors.password'))->not->toBeEmpty();
    });

    it('sanitizes malicious input', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/register', [
                'first_name' => '<script>alert("XSS")</script>',
                'last_name' => 'User',
                'email' => 'test@sena.edu.co',
                'password' => 'ValidP@ssw0rd123',
                'password_confirmation' => 'ValidP@ssw0rd123',
                'document_type' => 'CC',
                'document_number' => '1234567890',
                'phone' => '3001234567',
                'role' => 'usuario',
            ]);

        // Debe rechazar caracteres maliciosos
        $response->assertStatus(422);
        expect($response->json('errors.first_name'))->not->toBeEmpty();
    });

    it('validates email format strictly', function () {
        $invalidEmails = [
            'notanemail',
            'test@',
            '@example.com',
            'test@invalid',
        ];

        foreach ($invalidEmails as $email) {
            $response = postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertStatus(422);
            expect($response->json('errors.email'))->not->toBeEmpty();
        }
    });
});
