<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\ExternalApi\Models\ApiClient;
use Illuminate\Database\Seeder;

class ApiClientsSeeder extends Seeder
{
    public function run(): void
    {
        $apiKey = ApiClient::generateApiKey();

        ApiClient::create([
            'name' => 'Sistema Externo SENA',
            'description' => 'Cliente API de prueba para integración externa',
            'api_key' => $apiKey,
            'status' => 'active',
            'allowed_resources' => ['equipment', 'spaces'],
            'rate_limit' => 60,
        ]);

        // Mostrar la API Key generada (solo en desarrollo)
        $this->command->info('API Key generada: ' . $apiKey);
        $this->command->warn('⚠️  Guarda esta API Key, no se mostrará nuevamente');
    }
}
