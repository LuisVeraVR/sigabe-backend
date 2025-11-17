<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Users\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Crear permisos (idempotente)
        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission['name'], 'web');
        }

        // Crear roles y asignar permisos (idempotente)
        $this->createAdminProgramadorRole();
        $this->createAdminMediatecaRole();
        $this->createColaboradorRole();
        $this->createUsuarioRole();
    }

    private function getPermissions(): array
    {
        return [
            // Usuarios
            ['name' => 'users.view'],
            ['name' => 'users.create'],
            ['name' => 'users.edit'],
            ['name' => 'users.delete'],

            // Catálogo
            ['name' => 'catalog.view'],
            ['name' => 'catalog.create'],
            ['name' => 'catalog.edit'],
            ['name' => 'catalog.delete'],

            // Espacios
            ['name' => 'spaces.view'],
            ['name' => 'spaces.create'],
            ['name' => 'spaces.edit'],
            ['name' => 'spaces.delete'],

            // Equipos
            ['name' => 'equipment.view'],
            ['name' => 'equipment.create'],
            ['name' => 'equipment.edit'],
            ['name' => 'equipment.delete'],
            ['name' => 'equipment.maintenance'],

            // Préstamos
            ['name' => 'loans.view'],
            ['name' => 'loans.create'],
            ['name' => 'loans.approve'],
            ['name' => 'loans.return'],
            ['name' => 'loans.cancel'],

            // Reservas
            ['name' => 'reservations.view'],
            ['name' => 'reservations.create'],
            ['name' => 'reservations.approve'],
            ['name' => 'reservations.cancel'],

            // Incidentes
            ['name' => 'incidents.view'],
            ['name' => 'incidents.create'],
            ['name' => 'incidents.assign'],
            ['name' => 'incidents.resolve'],

            // Reportes
            ['name' => 'reports.view'],
            ['name' => 'reports.export'],

            // Configuración
            ['name' => 'settings.view'],
            ['name' => 'settings.edit'],

            // API Externa
            ['name' => 'api-clients.manage'],
        ];
    }

    private function createAdminProgramadorRole(): void
    {
        /** @var \Spatie\Permission\Models\Role $role */
        $role = Role::findOrCreate(UserRole::ADMIN_PROGRAMADOR->value, 'web');

        // Administrador tiene TODOS los permisos
        $role->syncPermissions(Permission::all());
    }

    private function createAdminMediatecaRole(): void
    {
        $role = Role::findOrCreate(UserRole::ADMIN_MEDIATECA->value, 'web');

        $role->syncPermissions([
            // Usuarios (solo ver)
            'users.view',

            // Catálogo completo
            'catalog.view',
            'catalog.create',
            'catalog.edit',
            'catalog.delete',

            // Espacios completo
            'spaces.view',
            'spaces.create',
            'spaces.edit',
            'spaces.delete',

            // Equipos (solo ver)
            'equipment.view',

            // Préstamos completo
            'loans.view',
            'loans.create',
            'loans.approve',
            'loans.return',
            'loans.cancel',

            // Reservas completo
            'reservations.view',
            'reservations.create',
            'reservations.approve',
            'reservations.cancel',

            // Incidentes (ver y crear)
            'incidents.view',
            'incidents.create',

            // Reportes completo
            'reports.view',
            'reports.export',
        ]);
    }

    private function createColaboradorRole(): void
    {
        $role = Role::findOrCreate(UserRole::COLABORADOR->value, 'web');

        $role->syncPermissions([
            // Equipos completo (gestión técnica)
            'equipment.view',
            'equipment.create',
            'equipment.edit',
            'equipment.maintenance',

            // Incidentes completo
            'incidents.view',
            'incidents.create',
            'incidents.assign',
            'incidents.resolve',

            // Préstamos (gestión operativa)
            'loans.view',
            'loans.return',

            // Espacios (solo ver)
            'spaces.view',
        ]);
    }

    private function createUsuarioRole(): void
    {
        $role = Role::findOrCreate(UserRole::USUARIO->value, 'web');

        $role->syncPermissions([
            // Catálogo (solo consulta)
            'catalog.view',

            // Espacios (solo consulta)
            'spaces.view',

            // Equipos (solo consulta)
            'equipment.view',

            // Préstamos (crear y ver propios)
            'loans.view',
            'loans.create',

            // Reservas (crear y ver propias)
            'reservations.view',
            'reservations.create',

            // Incidentes (crear reportes)
            'incidents.create',
        ]);
    }
}
