<?php

declare(strict_types=1);

namespace App\Traits;

use App\Domain\Shared\Models\AuditLog;

trait Auditable
{
    /**
     * Boot the trait
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditLog('created', 'Registro creado');
        });

        static::updated(function ($model) {
            if ($model->wasChanged() && !$model->wasRecentlyCreated) {
                $model->auditLog('updated', 'Registro actualizado', $model->getChanges());
            }
        });

        static::deleted(function ($model) {
            $model->auditLog('deleted', 'Registro eliminado');
        });
    }

    /**
     * Crear log de auditoría
     */
    protected function auditLog(string $action, string $description, ?array $changes = null): void
    {
        if (!config('sigabe.audit.enabled')) {
            return;
        }

        AuditLog::log(
            action: $action,
            module: $this->getAuditModule(),
            description: $description,
            record: $this,
            changes: $changes
        );
    }

    /**
     * Obtener nombre del módulo para auditoría
     */
    protected function getAuditModule(): string
    {
        return strtolower(class_basename($this));
    }
}
