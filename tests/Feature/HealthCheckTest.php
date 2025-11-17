<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

describe('Health Check Endpoint', function () {

    it('returns successful health check response', function () {
        $response = getJson('/api/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'system' => [
                        'name',
                        'version',
                        'environment',
                        'timezone',
                    ],
                    'timestamp',
                    'services' => [
                        'database',
                        'cache',
                    ],
                    'php' => [
                        'version',
                        'extensions',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('verifies system name is correct', function () {
        $response = getJson('/api/health');

        expect($response->json('data.system.name'))->toBe('SIGABE');
    });

    it('verifies database connection is healthy', function () {
        $response = getJson('/api/health');

        expect($response->json('data.services.database.status'))->toBe('healthy');
    });

    it('verifies cache system is healthy', function () {
        $response = getJson('/api/health');

        expect($response->json('data.services.cache.status'))->toBe('healthy');
    });

    it('returns correct content type', function () {
        $response = getJson('/api/health');

        $response->assertHeader('Content-Type', 'application/json');
    });
});
