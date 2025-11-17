<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Auth\Enums\DocumentType;
use Database\Factories\UserFactory; // ← AGREGAR ESTA LÍNEA
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    /**
     * Guard name para Spatie Permission
     */
    protected $guard_name = 'web';

    /**
     * Atributos asignables masivamente
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'document_type',
        'document_number',
        'phone',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * Atributos ocultos en serialización
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting de atributos
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => UserStatus::class,
        'document_type' => DocumentType::class,
        'last_login_at' => 'datetime',
    ];

    /**
     * Especificar Factory personalizado
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Configuración de Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'email', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Accessor: Nombre completo
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Verificar si el usuario está suspendido
     */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::SUSPENDED;
    }

    /**
     * Verificar si tiene un rol específico (helper adicional)
     */
    public function hasRoleEnum(UserRole $role): bool
    {
        return $this->hasRole($role->value);
    }

    /**
     * Verificar si es administrador
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            UserRole::ADMIN_PROGRAMADOR->value,
            UserRole::ADMIN_MEDIATECA->value,
        ]);
    }

    /**
     * Actualizar último login
     */
    public function updateLastLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Scope: Solo usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    /**
     * Scope: Por rol
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->role($role);
    }
}
