<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
        then: function () {
            // Configurar Rate Limiters
            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(config('sigabe.security.rate_limit.login_attempts'))
                    ->by($request->email . '|' . $request->ip())
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Demasiados intentos de inicio de sesión. Por favor, intente más tarde.',
                            'retry_after' => $headers['Retry-After'] ?? 60,
                        ], 429, $headers);
                    });
            });

            RateLimiter::for('api', function (Request $request) {
                $limit = $request->user()
                    ? config('sigabe.security.rate_limit.api_requests')
                    : config('sigabe.security.rate_limit.guest_requests');

                return Limit::perMinute($limit)
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Límite de solicitudes excedido. Por favor, espere un momento.',
                            'retry_after' => $headers['Retry-After'] ?? 60,
                        ], 429, $headers);
                    });
            });

            RateLimiter::for('external-api', function (Request $request) {
                return Limit::perMinute(config('sigabe.security.rate_limit.external_api'))
                    ->by($request->header('X-API-Key', $request->ip()))
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Límite de API excedido. Por favor, revise su plan.',
                            'retry_after' => $headers['Retry-After'] ?? 60,
                        ], 429, $headers);
                    });
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,

        ]);

        // Aplicar rate limiting global a rutas API
        $middleware->throttleApi('api');

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Manejar excepciones de autenticación para APIs
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado. Por favor, inicie sesión para continuar.',
                    'error' => 'Unauthenticated',
                ], 401);
            }
        });

        // Manejar excepciones de autorización (permisos) para APIs
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para realizar esta acción.',
                    'error' => 'Forbidden',
                ], 403);
            }
        });

        // Manejar excepciones de autorización generales
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene autorización para realizar esta acción.',
                    'error' => 'Forbidden',
                ], 403);
            }
        });
    })->create();
