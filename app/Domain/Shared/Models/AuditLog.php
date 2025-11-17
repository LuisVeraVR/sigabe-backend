<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_type',
        'record_id',
        'changes',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log de auditoría estático
     */
    public static function log(
        string $action,
        string $module,
        ?string $description = null,
        ?Model $record = null,
        ?array $changes = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_type' => $record ? get_class($record) : null,
            'record_id' => $record?->id,
            'changes' => $changes,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
