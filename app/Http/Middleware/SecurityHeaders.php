<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Agregar headers de seguridad a todas las respuestas
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = config('sigabe.security.headers');

        $response->headers->set('X-Content-Type-Options', $headers['x_content_type_options']);
        $response->headers->set('X-Frame-Options', $headers['x_frame_options']);
        $response->headers->set('X-XSS-Protection', $headers['x_xss_protection']);
        $response->headers->set('Referrer-Policy', $headers['referrer_policy']);
        $response->headers->set('Permissions-Policy', $headers['permissions_policy']);

        // Remover headers que exponen información del servidor
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
