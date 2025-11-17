<?php

declare(strict_types=1);

use App\Domain\Users\Models\User;
use App\Domain\Users\Enums\UserRole;
use Tests\Traits\WithRolesAndPermissions;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson; // ← AGREGAR ESTA LÍNEA
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
uses(WithRolesAndPermissions::class);

describe('Authorization', function () {

    beforeEach(function () {
        $this->seedRolesAndPermissions();
    });

    it('denies access to admin routes for non-admin users', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::USUARIO->value);

        $response = actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@sena.edu.co',
                'password' => 'ValidP@ssw0rd123',
                'password_confirmation' => 'ValidP@ssw0rd123',
                'document_type' => 'CC',
                'document_number' => '1234567890',
                'phone' => '3001234567',
                'role' => 'usuario',
            ]);

        $response->assertStatus(403);
    });

    it('allows access to admin routes for admin users', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN_PROGRAMADOR->value);

        $response = actingAs($admin, 'sanctum')
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'newuser@sena.edu.co',
                'password' => 'ValidP@ssw0rd123',
                'password_confirmation' => 'ValidP@ssw0rd123',
                'document_type' => 'CC',
                'document_number' => '9876543210',
                'phone' => '3009876543',
                'role' => 'usuario',
            ]);

        $response->assertStatus(201);
    });

    it('blocks suspended users from accessing any route', function () {
        $user = User::factory()->suspended()->create([
            'email' => 'suspended@sena.edu.co',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole(UserRole::USUARIO->value);

        // Intentar hacer login (debería fallar por cuenta suspendida)
        $response = postJson('/api/v1/auth/login', [
            'email' => 'suspended@sena.edu.co',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        expect($response->json('errors.email.0'))->toContain('suspendida');
    });

    it('requires authentication for protected routes', function () {
        $response = getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    });
});
