<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Users\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Verificar que el usuario tenga el rol requerido
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo o suspendido',
            ], 403);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este recurso',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
