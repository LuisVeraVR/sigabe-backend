<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\DTOs\LoginCredentials;
use App\Domain\Auth\DTOs\RegisterData;
use App\Domain\Auth\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Registrar nuevo usuario
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = RegisterData::fromRequest($request->validated());
        $user = $this->authService->register($data);

        return $this->createdResponse(
            data: new UserResource($user),
            message: 'Usuario registrado exitosamente'
        );
    }

    /**
     * Iniciar sesión
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = LoginCredentials::fromRequest($request->validated());
        $result = $this->authService->login($credentials);

        return $this->successResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
            ],
            message: 'Inicio de sesión exitoso'
        );
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            message: 'Sesión cerrada exitosamente'
        );
    }

    /**
     * Cerrar todas las sesiones
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return $this->successResponse(
            message: 'Todas las sesiones han sido cerradas'
        );
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'permissions');

        return $this->successResponse(
            data: new UserResource($user),
            message: 'Información del usuario obtenida exitosamente'
        );
    }
}
