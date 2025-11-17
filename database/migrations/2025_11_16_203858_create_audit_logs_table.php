<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100); // created, updated, deleted, login, logout
            $table->string('module', 50); // users, loans, reservations, equipment
            $table->string('record_type', 100)->nullable(); // Clase del modelo
            $table->unsignedBigInteger('record_id')->nullable(); // ID del registro
            $table->json('changes')->nullable(); // Cambios realizados (before/after)
            $table->text('description')->nullable();
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action']);
            $table->index(['record_type', 'record_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
