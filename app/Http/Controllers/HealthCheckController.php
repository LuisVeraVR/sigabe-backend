<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    use ApiResponse;

    /**
     * Verificar el estado del sistema
     */
    public function __invoke(): JsonResponse
    {
        $status = $this->checkSystemHealth();

        return $this->successResponse(
            data: $status,
            message: 'Estado del sistema obtenido exitosamente'
        );
    }

    /**
     * Verificar componentes del sistema
     */
    private function checkSystemHealth(): array
    {
        return [
            'system' => [
                'name' => config('sigabe.system.name'),
                'version' => config('sigabe.system.version'),
                'environment' => app()->environment(),
                'timezone' => config('sigabe.system.timezone'),
            ],
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'extensions' => [
                    'pgsql' => extension_loaded('pgsql'),
                    'redis' => extension_loaded('redis'),
                    'mbstring' => extension_loaded('mbstring'),
                ],
            ],
        ];
    }

    /**
     * Verificar conexión a base de datos
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();

            return [
                'status' => 'healthy',
                'connection' => DB::getDefaultConnection(),
                'database' => $databaseName,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar sistema de caché
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
