<?php

declare(strict_types=1);

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentBrand;
use App\Domain\Equipment\Models\EquipmentType;
use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithRolesAndPermissions;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class); // ← CRÍTICO: Esto ejecuta migraciones
uses(WithRolesAndPermissions::class);

beforeEach(function () {
    $this->seedRolesAndPermissions();

    // Crear tipo y marca de equipo para las pruebas
    $this->equipmentType = EquipmentType::create([
        'name' => 'Laptop',
        'slug' => 'laptop',
        'description' => 'Laptop para pruebas',
        'average_loan_duration_hours' => 24,
    ]);

    $this->equipmentBrand = EquipmentBrand::create([
        'name' => 'HP',
        'slug' => 'hp',
        'country' => 'USA',
    ]);
});

describe('Equipment CRUD', function () {

    it('colaborador can list equipment', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Equipment::factory()->count(3)->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/equipment');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'model',
                        'serial_number',
                        'asset_code',
                        'status',
                        'condition',
                    ]
                ],
                'meta',
            ]);
    });

    it('colaborador can create equipment', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/equipment', [
                'equipment_type_id' => $this->equipmentType->id,
                'equipment_brand_id' => $this->equipmentBrand->id,
                'name' => 'HP Pavilion',
                'model' => 'Pavilion 15',
                'serial_number' => 'SN-TEST-001',
                'condition' => 'excellent',
                'purchase_date' => '2024-01-15',
                'purchase_cost' => 2500000,
                'specifications' => [
                    'ram' => '16GB',
                    'storage' => '512GB SSD',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Hp Pavilion')
            ->assertJsonPath('data.serial_number', 'SN-TEST-001');

        $this->assertDatabaseHas('equipment', [
            'serial_number' => 'SN-TEST-001',
        ]);
    });

    it('requires unique serial number', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'serial_number' => 'SN-DUPLICATE',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson('/api/v1/equipment', [
                'equipment_type_id' => $this->equipmentType->id,
                'name' => 'Test Equipment',
                'serial_number' => 'SN-DUPLICATE',
                'condition' => 'good',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serial_number']);
    });

    it('colaborador can update equipment', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'name' => 'Old Name',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->putJson("/api/v1/equipment/{$equipment->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'name' => 'New Name',
        ]);
    });

    it('colaborador can delete equipment', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'available',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->deleteJson("/api/v1/equipment/{$equipment->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('equipment', [
            'id' => $equipment->id,
        ]);
    });

    it('cannot delete equipment on loan', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'on_loan',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->deleteJson("/api/v1/equipment/{$equipment->id}");

        $response->assertStatus(400);

        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'deleted_at' => null,
        ]);
    });

    it('usuario cannot create equipment', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/equipment', [
                'equipment_type_id' => $this->equipmentType->id,
                'name' => 'Test Equipment',
                'serial_number' => 'SN-TEST-001',
                'condition' => 'good',
            ]);

        $response->assertStatus(403);
    });
});

describe('Equipment Status', function () {

    it('colaborador can change equipment status', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'available',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->patchJson("/api/v1/equipment/{$equipment->id}/status", [
                'status' => 'maintenance',
                'reason' => 'Mantenimiento preventivo programado',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'maintenance');

        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'status' => 'maintenance',
        ]);
    });

    it('can check equipment availability', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'available',
            'condition' => 'good',
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/equipment/{$equipment->id}/availability");

        $response->assertStatus(200)
            ->assertJsonPath('data.available', true);
    });

    it('reports unavailable equipment', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'on_loan',
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/equipment/{$equipment->id}/availability");

        $response->assertStatus(200)
            ->assertJsonPath('data.available', false);
    });
});

describe('Equipment Search and Filters', function () {

    it('can search equipment by name', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'name' => 'HP Pavilion Laptop',
            'serial_number' => 'SN-001',
        ]);

        Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'name' => 'Dell Inspiron Desktop',
            'serial_number' => 'SN-002',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/equipment?search=HP');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(1);
        expect($data[0]['name'])->toContain('HP');
    });

    it('can filter by status', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Equipment::factory()->count(2)->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'available',
        ]);

        Equipment::factory()->create([
            'equipment_type_id' => $this->equipmentType->id,
            'equipment_brand_id' => $this->equipmentBrand->id,
            'status' => 'maintenance',
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/equipment?status=available');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });
});
