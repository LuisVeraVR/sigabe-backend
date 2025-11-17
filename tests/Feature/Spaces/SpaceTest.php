<?php

declare(strict_types=1);

use App\Domain\Spaces\Enums\SpaceStatus;
use App\Domain\Spaces\Enums\SpaceType;
use App\Domain\Spaces\Models\Space;
use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithRolesAndPermissions;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);
uses(WithRolesAndPermissions::class);

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Space CRUD', function () {

    it('colaborador can create space', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'LAB-101',
                'name' => 'Laboratorio de Computo',
                'building' => 'Edificio A',
                'floor' => '1',
                'location_description' => 'Ala este',
                'capacity' => 30,
                'space_type' => 'lab',
                'description' => 'Laboratorio equipado con 30 computadoras',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'LAB-101')
            ->assertJsonPath('data.name', 'Laboratorio de Computo')
            ->assertJsonPath('data.space_type.value', 'lab')
            ->assertJsonPath('data.status.value', 'available');

        $this->assertDatabaseHas('spaces', [
            'code' => 'LAB-101',
            'name' => 'Laboratorio de Computo',
            'space_type' => SpaceType::LAB->value,
            'status' => SpaceStatus::AVAILABLE->value,
        ]);
    });

    it('creates space with default classroom type', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'AUL-201',
                'name' => 'Aula 201',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.space_type.value', 'classroom');
    });

    it('validates required fields', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/spaces', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name']);
    });

    it('validates code is unique', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        Space::factory()->create(['code' => 'LAB-101']);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'LAB-101',
                'name' => 'Otro laboratorio',
            ]);

        $response->assertStatus(400);
    });

    it('validates space_type is valid enum value', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'TEST-001',
                'name' => 'Test Space',
                'space_type' => 'invalid_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['space_type']);
    });

    it('can list spaces', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(5)->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
    });

    it('can view space details', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $space = Space::factory()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'space_type',
                    'status',
                ],
            ]);
    });

    it('can update space', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->create([
            'code' => 'LAB-101',
            'name' => 'Laboratorio Original',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->putJson("/api/v1/spaces/{$space->id}", [
                'name' => 'Laboratorio Actualizado',
                'capacity' => 40,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Laboratorio Actualizado')
            ->assertJsonPath('data.capacity', 40);

        $this->assertDatabaseHas('spaces', [
            'id' => $space->id,
            'name' => 'Laboratorio Actualizado',
            'capacity' => 40,
        ]);
    });

    it('can delete space', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->available()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->deleteJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('spaces', [
            'id' => $space->id,
        ]);
    });

    it('cannot delete reserved space', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->reserved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->deleteJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(400);
    });
});

describe('Space Status Management', function () {

    it('colaborador can mark space as available', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->unavailable()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/mark-available");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'available');

        $this->assertDatabaseHas('spaces', [
            'id' => $space->id,
            'status' => SpaceStatus::AVAILABLE->value,
        ]);
    });

    it('colaborador can mark space as unavailable', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->available()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/mark-unavailable");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'unavailable');

        $this->assertDatabaseHas('spaces', [
            'id' => $space->id,
            'status' => SpaceStatus::UNAVAILABLE->value,
        ]);
    });

    it('colaborador can mark space as in maintenance', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->available()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/mark-maintenance");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'maintenance');

        $this->assertDatabaseHas('spaces', [
            'id' => $space->id,
            'status' => SpaceStatus::MAINTENANCE->value,
        ]);
    });

    it('cannot mark reserved space as unavailable', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->reserved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/mark-unavailable");

        $response->assertStatus(400);
    });

    it('cannot mark reserved space as in maintenance', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::ADMIN_MEDIATECA->value);

        $space = Space::factory()->reserved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/mark-maintenance");

        $response->assertStatus(400);
    });
});

describe('Space Filtering', function () {

    it('can filter by space type', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(3)->classroom()->create();
        Space::factory()->count(2)->lab()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces?space_type=lab');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });

    it('can filter by status', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(3)->available()->create();
        Space::factory()->count(2)->unavailable()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces?status=available');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
    });

    it('can filter by building', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(2)->inBuilding('Edificio A')->create();
        Space::factory()->count(3)->inBuilding('Edificio B')->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces?building=Edificio A');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });

    it('can filter by floor', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(2)->onFloor('1')->create();
        Space::factory()->count(3)->onFloor('2')->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces?floor=1');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });

    it('can filter by minimum capacity', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->withCapacity(20)->create();
        Space::factory()->withCapacity(50)->create();
        Space::factory()->withCapacity(100)->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces?min_capacity=50');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });

    it('can search by code and name', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->create([
            'code' => 'LAB-101',
            'name' => 'Laboratorio de Redes',
        ]);
        Space::factory()->create([
            'code' => 'AUL-202',
            'name' => 'Aula de Computo',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces?search=LAB');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
    });
});

describe('Space Listing', function () {

    it('can list available spaces', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(4)->available()->create();
        Space::factory()->count(2)->unavailable()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces/available/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(4);
    });
});

describe('Space Statistics', function () {

    it('colaborador can view statistics', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->count(3)->available()->create();
        Space::factory()->count(2)->unavailable()->create();
        Space::factory()->maintenance()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces/stats/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'by_status',
                    'by_type',
                ],
            ]);
    });
});

describe('Space Utilities', function () {

    it('can get list of buildings', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->inBuilding('Edificio A')->create();
        Space::factory()->inBuilding('Edificio B')->create();
        Space::factory()->inBuilding('Edificio A')->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces/utilities/buildings');

        $response->assertStatus(200);

        $buildings = $response->json('data');
        expect(count($buildings))->toBe(2);
    });

    it('can get list of floors', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Space::factory()->onFloor('1')->create();
        Space::factory()->onFloor('2')->create();
        Space::factory()->onFloor('1')->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/spaces/utilities/floors');

        $response->assertStatus(200);

        $floors = $response->json('data');
        expect(count($floors))->toBe(2);
    });
});

describe('Space Permissions', function () {

    it('usuario can view spaces', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $space = Space::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(200);
    });

    it('usuario cannot create spaces', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'TEST-001',
                'name' => 'Test Space',
            ]);

        $response->assertStatus(403);
    });

    it('usuario cannot edit spaces', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $space = Space::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->putJson("/api/v1/spaces/{$space->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    });

    it('usuario cannot delete spaces', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $space = Space::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->deleteJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(403);
    });

    it('colaborador can view but not manage spaces', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $space = Space::factory()->create();

        // Can view
        $response = actingAs($colaborador, 'sanctum')
            ->getJson("/api/v1/spaces/{$space->id}");
        $response->assertStatus(200);

        // Cannot create
        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'TEST-001',
                'name' => 'Test Space',
            ]);
        $response->assertStatus(403);
    });

    it('admin can perform all actions', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN_PROGRAMADOR->value);

        // Create
        $response = actingAs($admin, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'code' => 'ADMIN-001',
                'name' => 'Admin Space',
            ]);
        $response->assertStatus(201);

        $space = Space::first();

        // Update
        $response = actingAs($admin, 'sanctum')
            ->putJson("/api/v1/spaces/{$space->id}", [
                'name' => 'Updated Space',
            ]);
        $response->assertStatus(200);

        // Delete
        $response = actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/spaces/{$space->id}");
        $response->assertStatus(200);
    });
});
