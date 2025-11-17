<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\LoginCredentials;
use App\Domain\Auth\DTOs\RegisterData;
use App\Domain\Shared\Models\AuditLog;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Registrar nuevo usuario
     */
    public function register(RegisterData $data): User
    {
        $user = User::create([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'phone' => $data->phone,
            'status' => UserStatus::ACTIVE,
        ]);

        // Asignar rol
        $user->assignRole($data->role);

        // Auditar
        AuditLog::log(
            action: 'user_created',
            module: 'auth',
            description: "Usuario registrado: {$user->email}",
            record: $user
        );

        return $user;
    }

    /**
     * Autenticar usuario y generar token
     */
    public function login(LoginCredentials $credentials): array
    {
        $user = User::where('email', $credentials->email)->first();

        // Verificar credenciales
        if (!$user || !Hash::check($credentials->password, $user->password)) {
            // Auditar intento fallido
            AuditLog::log(
                action: 'failed_login',
                module: 'auth',
                description: "Intento de login fallido para: {$credentials->email}"
            );

            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Verificar estado del usuario
        if (!$user->isActive()) {
            $status = $user->isSuspended() ? 'suspendida' : 'inactiva';

            AuditLog::log(
                action: 'blocked_login',
                module: 'auth',
                description: "Intento de login con cuenta {$status}: {$user->email}",
                record: $user
            );

            throw ValidationException::withMessages([
                'email' => ["Su cuenta está {$status}. Contacte al administrador."],
            ]);
        }

        // Limpiar tokens antiguos si excede el límite
        $this->cleanOldTokens($user);

        // Crear token
        $token = $user->createToken(
            $credentials->deviceName,
            ['*'],
            now()->addMinutes(config('sigabe.security.token.expiration_minutes'))
        )->plainTextToken;

        // Actualizar último login
        $user->updateLastLogin();

        // Auditar login exitoso
        AuditLog::log(
            action: 'login',
            module: 'auth',
            description: "Login exitoso: {$user->email}",
            record: $user
        );

        return [
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sigabe.security.token.expiration_minutes') * 60, // en segundos
        ];
    }

    /**
     * Cerrar sesión (revocar token actual)
     */
    public function logout(User $user): void
    {
        // Revocar token actual
        $user->currentAccessToken()->delete();

        AuditLog::log(
            action: 'logout',
            module: 'auth',
            description: "Logout: {$user->email}",
            record: $user
        );
    }

    /**
     * Cerrar todas las sesiones (revocar todos los tokens)
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();

        AuditLog::log(
            action: 'logout_all',
            module: 'auth',
            description: "Logout de todas las sesiones: {$user->email}",
            record: $user
        );
    }

    /**
     * Limpiar tokens antiguos si excede el límite
     */
    private function cleanOldTokens(User $user): void
    {
        $maxTokens = config('sigabe.security.token.max_tokens_per_user');
        $tokensCount = $user->tokens()->count();

        if ($tokensCount >= $maxTokens) {
            // Eliminar los tokens más antiguos
            $user->tokens()
                ->orderBy('created_at', 'asc')
                ->limit($tokensCount - $maxTokens + 1)
                ->delete();
        }
    }
}
