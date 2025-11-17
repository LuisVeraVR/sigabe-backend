<?php

declare(strict_types=1);

use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Models\User;
use Tests\Traits\WithRolesAndPermissions;
use function Pest\Laravel\postJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
uses(WithRolesAndPermissions::class);

describe('Authentication', function () {

    beforeEach(function () {
        $this->seedRolesAndPermissions();
    });

    it('can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@sena.edu.co',
            'password' => bcrypt('password'),
        ]);

        $response = postJson('/api/v1/auth/login', [
            'email' => 'test@sena.edu.co',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'email', 'full_name'],
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        expect($response->json('data.token_type'))->toBe('Bearer');
    });

    it('cannot login with invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@sena.edu.co',
            'password' => bcrypt('password'),
        ]);

        $response = postJson('/api/v1/auth/login', [
            'email' => 'test@sena.edu.co',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('cannot login with suspended account', function () {
        $user = User::factory()->suspended()->create([
            'email' => 'test@sena.edu.co',
            'password' => bcrypt('password'),
        ]);

        $response = postJson('/api/v1/auth/login', [
            'email' => 'test@sena.edu.co',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        expect($response->json('errors.email.0'))->toContain('suspendida');
    });

    it('can get authenticated user info', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    });

    it('can logout successfully', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Verificar que el usuario puede acceder antes del logout
        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/me');
        $response->assertStatus(200);

        // Hacer logout
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
            ]);

        // Verificar que el token ya no existe en la base de datos
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    it('only admins can register users', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::USUARIO->value);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'new@sena.edu.co',
                'password' => 'ValidP@ssw0rd123',
                'password_confirmation' => 'ValidP@ssw0rd123',
                'document_type' => 'CC',
                'document_number' => '9876543210',
                'phone' => '3001234567',
                'role' => 'usuario',
            ]);

        $response->assertStatus(403);
    });
});
