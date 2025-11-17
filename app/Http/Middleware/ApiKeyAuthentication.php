<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\ExternalApi\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    /**
     * Verificar API Key para clientes externos
     */
    public function handle(Request $request, Closure $next, ?string $resource = null): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key requerida',
            ], 401);
        }

        $client = ApiClient::where('api_key', $apiKey)->active()->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'API Key inválida o inactiva',
            ], 401);
        }

        if ($resource && !$client->canAccessResource($resource)) {
            return response()->json([
                'success' => false,
                'message' => "No tiene acceso al recurso: {$resource}",
                'allowed_resources' => $client->allowed_resources,
            ], 403);
        }

        $client->updateLastUsed($request->ip());

        $request->merge(['api_client' => $client]);

        return $next($request);
    }
}
