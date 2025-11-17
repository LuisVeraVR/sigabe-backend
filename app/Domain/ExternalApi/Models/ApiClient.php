<?php

declare(strict_types=1);

namespace App\Domain\ExternalApi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'api_key',
        'status',
        'allowed_resources',
        'rate_limit',
        'last_used_at',
        'last_used_ip',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'allowed_resources' => 'array',
        'last_used_at' => 'datetime',
        'rate_limit' => 'integer',
    ];

    /**
     * Generar API Key única
     */
    public static function generateApiKey(): string
    {
        return hash('sha256', Str::random(60) . time());
    }

    /**
     * Verificar si el cliente está activo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verificar si tiene acceso a un recurso
     */
    public function canAccessResource(string $resource): bool
    {
        if (empty($this->allowed_resources)) {
            return false;
        }

        return in_array($resource, $this->allowed_resources);
    }

    /**
     * Actualizar último uso
     */
    public function updateLastUsed(?string $ip = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Scope: Solo clientes activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
