<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();

            // Identificación del equipo
            $table->string('asset_code')->unique();     // código interno / placa
            $table->string('name', 150);                // nombre del equipo
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable();

            // Relaciones con tipo y marca
            $table->foreignId('equipment_type_id')
                ->constrained('equipment_types')
                ->cascadeOnDelete();

            $table->foreignId('equipment_brand_id')
                ->nullable()
                ->constrained('equipment_brands')
                ->nullOnDelete();

            // Relación con espacio actual (ubicación física)
            $table->foreignId('current_space_id')
                ->nullable()
                ->constrained('spaces')
                ->nullOnDelete();

            // Información de compra / garantía
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->date('warranty_expiration_date')->nullable();

            // Estado físico (ligado a EquipmentCondition enum)
            $table->enum('condition', [
                'excellent',
                'good',
                'fair',
                'poor',
                'damaged',
            ])->default('excellent');

            // Estado operativo (ligado a EquipmentStatus enum)
            $table->enum('status', [
                'available',
                'on_loan',
                'reserved',
                'maintenance',
                'damaged',
                'retired',
            ])->default('available');

            // Info técnica / extra
            $table->json('specifications')->nullable();       // especificaciones técnicas
            $table->json('requires_accessories')->nullable(); // accesorios requeridos
            $table->text('description')->nullable();          // descripción libre
            $table->json('metadata')->nullable();             // cualquier otra info extra
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes(); // 👈 NECESARIO por el SoftDeletes del modelo

            // Índices
            $table->index('equipment_type_id');
            $table->index('equipment_brand_id');
            $table->index('current_space_id');
            $table->index('status');
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
