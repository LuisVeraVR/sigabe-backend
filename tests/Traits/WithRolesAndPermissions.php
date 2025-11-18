<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\Users\Enums\UserRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait WithRolesAndPermissions
{
    protected function seedRolesAndPermissions(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos básicos con AMBOS guards
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'catalog.view',
            'catalog.create',
            'catalog.edit',
            'catalog.delete',
            'spaces.view',
            'spaces.create',
            'spaces.edit',
            'spaces.delete',
            'equipment.view',
            'equipment.create',
            'equipment.edit',
            'equipment.delete',
            'loans.view',
            'loans.create',
            'loans.approve',
            'loans.return',
            'reservations.view',
            'reservations.create',
            'reservations.approve',
            'incidents.view',
            'incidents.create',
            'incidents.resolve',
            'reports.view',
            'reports.export',
            'settings.view',
            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            // Crear para web
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            // Crear para sanctum (API)
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }

        // Crear roles
        $this->createAdminProgramadorRole();
        $this->createAdminMediatecaRole();
        $this->createColaboradorRole();
        $this->createUsuarioRole();
    }

    private function createAdminProgramadorRole(): void
    {
        // Web
        $roleWeb = Role::firstOrCreate([
            'name' => UserRole::ADMIN_PROGRAMADOR->value,
            'guard_name' => 'web',
        ]);
        $roleWeb->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // Sanctum
        $roleSanctum = Role::firstOrCreate([
            'name' => UserRole::ADMIN_PROGRAMADOR->value,
            'guard_name' => 'sanctum',
        ]);
        $roleSanctum->givePermissionTo(Permission::where('guard_name', 'sanctum')->get());
    }

    private function createAdminMediatecaRole(): void
    {
        $permissions = [
            'users.view',
            'catalog.view',
            'catalog.create',
            'catalog.edit',
            'catalog.delete',
            'spaces.view',
            'spaces.create',
            'spaces.edit',
            'spaces.delete',
            'equipment.view',
            'loans.view',
            'loans.create',
            'loans.approve',
            'loans.return',
            'reservations.view',
            'reservations.create',
            'reservations.approve',
            'incidents.view',
            'incidents.create',
            'reports.view',
            'reports.export',
        ];

        // Web
        $roleWeb = Role::firstOrCreate([
            'name' => UserRole::ADMIN_MEDIATECA->value,
            'guard_name' => 'web',
        ]);
        $roleWeb->givePermissionTo(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->get()
        );

        // Sanctum
        $roleSanctum = Role::firstOrCreate([
            'name' => UserRole::ADMIN_MEDIATECA->value,
            'guard_name' => 'sanctum',
        ]);
        $roleSanctum->givePermissionTo(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', 'sanctum')
                ->get()
        );
    }

    private function createColaboradorRole(): void
    {
        $permissions = [
            'equipment.view',
            'equipment.create',
            'equipment.edit',
            'equipment.delete',
            'incidents.view',
            'incidents.create',
            'incidents.resolve',
            'loans.view',
            'loans.create',
            'loans.approve',
            'loans.return',
            'reservations.view',
            'reservations.create',
            'reservations.approve',
            'spaces.view',
        ];

        // Web
        $roleWeb = Role::firstOrCreate([
            'name' => UserRole::COLABORADOR->value,
            'guard_name' => 'web',
        ]);
        $roleWeb->givePermissionTo(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->get()
        );

        // Sanctum
        $roleSanctum = Role::firstOrCreate([
            'name' => UserRole::COLABORADOR->value,
            'guard_name' => 'sanctum',
        ]);
        $roleSanctum->givePermissionTo(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', 'sanctum')
                ->get()
        );
    }

    private function createUsuarioRole(): void
    {
        $permissions = [
            'catalog.view',
            'spaces.view',
            'equipment.view',
            'loans.view',
            'loans.create',
            'reservations.view',
            'reservations.create',
            'incidents.view',
            'incidents.create',
        ];

        // Web
        $roleWeb = Role::firstOrCreate([
            'name' => UserRole::USUARIO->value,
            'guard_name' => 'web',
        ]);
        $roleWeb->givePermissionTo(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->get()
        );

        // Sanctum
        $roleSanctum = Role::firstOrCreate([
            'name' => UserRole::USUARIO->value,
            'guard_name' => 'sanctum',
        ]);
        $roleSanctum->givePermissionTo(
            Permission::whereIn('name', $permissions)
                ->where('guard_name', 'sanctum')
                ->get()
        );
    }
}
