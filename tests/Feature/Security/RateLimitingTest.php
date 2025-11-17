<?php

declare(strict_types=1);

use App\Domain\Users\Models\User;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

describe('Rate Limiting', function () {

    it('blocks login after too many attempts', function () {
        $maxAttempts = config('sigabe.security.rate_limit.login_attempts');

        for ($i = 0; $i < $maxAttempts; $i++) {
            postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong_password',
            ]);
        }

        $response = postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(429);
        expect($response->json('message'))->toContain('Demasiados intentos');
    });

    it('allows requests within rate limit', function () {
        /** @var \App\Domain\Users\Models\User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $limit = config('sigabe.security.rate_limit.api_requests');

        for ($i = 0; $i < min(3, $limit - 1); $i++) {
            $response = getJson('/api/health', [
                'Authorization' => 'Bearer ' . $token,
            ]);

            $response->assertStatus(200);
        }
    });
});
