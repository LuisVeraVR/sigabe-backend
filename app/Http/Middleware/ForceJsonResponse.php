<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Forzar que todas las respuestas sean JSON en rutas API
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Forzar el header Accept a application/json
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
