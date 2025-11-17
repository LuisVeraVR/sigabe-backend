<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class, RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Crear usuario autenticado para tests
 */
function actingAsUser(string $role = 'usuario'): \App\Domain\Users\Models\User
{
    $user = \App\Domain\Users\Models\User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user, 'sanctum');
    return $user;
}

/**
 * Crear API key para tests de API externa
 */
function withApiKey(): void
{
    $client = \App\Domain\ExternalApi\Models\ApiClient::create([
        'name' => 'Test Client',
        'api_key' => 'test_api_key_12345',
        'status' => 'active',
        'allowed_resources' => ['equipment', 'spaces'],
        'rate_limit' => 60,
    ]);

    test()->withHeader('X-API-Key', 'test_api_key_12345');
}
