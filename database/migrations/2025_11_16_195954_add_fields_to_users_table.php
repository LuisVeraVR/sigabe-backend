<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar campo name por defecto y agregar nombres separados
            $table->dropColumn('name');

            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('document_type', 20)->nullable()->after('last_name'); // CC, TI, CE, PAS
            $table->string('document_number', 50)->nullable()->unique()->after('document_type');
            $table->string('phone', 20)->nullable()->after('document_number');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');

            $table->dropColumn([
                'first_name',
                'last_name',
                'document_type',
                'document_number',
                'phone',
                'status',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
