<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description')->nullable();
            $table->string('api_key', 64)->unique();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('allowed_resources')->nullable();
            $table->integer('rate_limit')->default(60);
            $table->timestamp('last_used_at')->nullable();
            $table->ipAddress('last_used_ip')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('api_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
