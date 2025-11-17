<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditApiRequests
{
    /**
     * Auditar requests a la API
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        if (!config('sigabe.audit.log_api_requests')) {
            return $response;
        }

        dispatch(function () use ($request, $response) {
            AuditLog::log(
                action: strtolower($request->method()),
                module: 'api',
                description: "{$request->method()} {$request->path()}",
                changes: [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                ]
            );
        })->afterResponse();

        return $response;
    }
}
