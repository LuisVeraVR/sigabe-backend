<?php

declare(strict_types=1);

namespace App\Domain\Equipment\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Database\Factories\EquipmentBrandFactory;

class EquipmentBrand extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected static function newFactory()
    {
        return EquipmentBrandFactory::new();
    }
    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'website',
        'country',
        'is_active',
    ];

    protected $casts = [
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
     * Relación: Una marca tiene muchos equipos
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * Scope: Solo marcas activas
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
}
