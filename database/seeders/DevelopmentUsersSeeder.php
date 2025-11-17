<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Auth\Enums\DocumentType;
use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->createAdminProgramador();
        $this->createAdminMediateca();
        $this->createColaborador();
        $this->createUsuario();
    }

    private function createAdminProgramador(): void
    {
        $user = User::create([
            'first_name' => 'Luis',
            'last_name' => 'Vera',
            'email' => 'admin@sigabe.sena.edu.co',
            'password' => Hash::make('password'),
            'document_type' => DocumentType::CC,
            'document_number' => '1000000001',
            'phone' => '3001234567',
            'status' => UserStatus::ACTIVE,
        ]);

        $user->assignRole(UserRole::ADMIN_PROGRAMADOR->value);
    }

    private function createAdminMediateca(): void
    {
        $user = User::create([
            'first_name' => 'María',
            'last_name' => 'González',
            'email' => 'mediateca@sigabe.sena.edu.co',
            'password' => Hash::make('password'),
            'document_type' => DocumentType::CC,
            'document_number' => '1000000002',
            'phone' => '3009876543',
            'status' => UserStatus::ACTIVE,
        ]);

        $user->assignRole(UserRole::ADMIN_MEDIATECA->value);
    }

    private function createColaborador(): void
    {
        $user = User::create([
            'first_name' => 'Carlos',
            'last_name' => 'Rodríguez',
            'email' => 'tecnico@sigabe.sena.edu.co',
            'password' => Hash::make('password'),
            'document_type' => DocumentType::CC,
            'document_number' => '1000000003',
            'phone' => '3157654321',
            'status' => UserStatus::ACTIVE,
        ]);

        $user->assignRole(UserRole::COLABORADOR->value);
    }

    private function createUsuario(): void
    {
        $user = User::create([
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'email' => 'usuario@sigabe.sena.edu.co',
            'password' => Hash::make('password'),
            'document_type' => DocumentType::CC,
            'document_number' => '1000000004',
            'phone' => '3201112233',
            'status' => UserStatus::ACTIVE,
        ]);

        $user->assignRole(UserRole::USUARIO->value);
    }
}
