<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Models;

use App\Traits\Auditable;
use Database\Factories\EquipmentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EquipmentType extends Model
{
    use HasFactory, SoftDeletes, Auditable;


    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'requires_training',
        'average_loan_duration_hours',
        'is_active',
    ];

    protected $casts = [
        'requires_training' => 'boolean',
        'average_loan_duration_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Relación: Un tipo tiene muchos equipos
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * Scope: Solo tipos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor: Conteo de equipos
     */
    public function getEquipmentCountAttribute(): int
    {
        return $this->equipment()->count();
    }

    /**
     * Accessor: Equipos disponibles
     */
    public function getAvailableEquipmentCountAttribute(): int
    {
        return $this->equipment()->where('status', 'available')->count();
    }
    protected static function newFactory()
    {
        return EquipmentTypeFactory::new();
    }
}
