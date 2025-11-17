<?php

declare(strict_types=1);

use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Loans\Enums\LoanStatus;
use App\Domain\Loans\Models\Loan;
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

describe('Loan CRUD', function () {

    it('usuario can create loan request', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/loans', [
                'equipment_id' => $equipment->id,
                'expected_return_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Necesito este equipo para un proyecto',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.user.id', $usuario->id)
            ->assertJsonPath('data.equipment.id', $equipment->id);

        $this->assertDatabaseHas('loans', [
            'user_id' => $usuario->id,
            'equipment_id' => $equipment->id,
            'status' => LoanStatus::PENDING->value,
        ]);
    });

    it('cannot create loan if equipment is not available', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::MAINTENANCE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/loans', [
                'equipment_id' => $equipment->id,
                'expected_return_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(400);
    });

    it('cannot create loan if user has 3 active loans', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        // Crear 3 préstamos activos
        Loan::factory()->count(3)->approved()->create([
            'user_id' => $usuario->id,
        ]);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/loans', [
                'equipment_id' => $equipment->id,
                'expected_return_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(400);
    });

    it('validates required fields', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/loans', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id', 'expected_return_date']);
    });

    it('validates expected return date must be future', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/loans', [
                'equipment_id' => $equipment->id,
                'expected_return_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expected_return_date']);
    });
});

describe('Loan Approval', function () {

    it('colaborador can approve loan', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $loan = Loan::factory()->pending()->create([
            'equipment_id' => Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE])->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'approved');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => LoanStatus::APPROVED->value,
            'approved_by' => $colaborador->id,
        ]);

        // Verificar que el equipo cambió de estado
        $this->assertDatabaseHas('equipment', [
            'id' => $loan->equipment_id,
            'status' => EquipmentStatus::ON_LOAN->value,
        ]);
    });

    it('usuario cannot approve loans', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $loan = Loan::factory()->pending()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/approve");

        $response->assertStatus(403);
    });

    it('cannot approve already approved loan', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $loan = Loan::factory()->approved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/approve");

        $response->assertStatus(400);
    });
});

describe('Loan Rejection', function () {

    it('colaborador can reject loan', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $loan = Loan::factory()->pending()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/reject", [
                'rejection_reason' => 'Equipo no disponible para este período',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'rejected');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => LoanStatus::REJECTED->value,
            'rejection_reason' => 'Equipo no disponible para este período',
        ]);
    });

    it('requires rejection reason', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $loan = Loan::factory()->pending()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    });
});

describe('Loan Return', function () {

    it('colaborador can return equipment', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::ON_LOAN]);
        $loan = Loan::factory()->approved()->create([
            'equipment_id' => $equipment->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'returned');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => LoanStatus::RETURNED->value,
        ]);

        // Verificar que el equipo volvió a disponible
        $this->assertDatabaseHas('equipment', [
            'id' => $loan->equipment_id,
            'status' => EquipmentStatus::AVAILABLE->value,
        ]);
    });

    it('cannot return loan that is not approved', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $loan = Loan::factory()->pending()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(400);
    });

    it('usuario cannot return equipment', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $loan = Loan::factory()->approved()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(403);
    });
});

describe('Loan Listing', function () {

    it('can list loans', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Loan::factory()->count(3)->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/loans');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
    });

    it('can list user active loans', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        Loan::factory()->count(2)->approved()->create(['user_id' => $usuario->id]);
        Loan::factory()->returned()->create(['user_id' => $usuario->id]);

        $response = actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/loans/me/active');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });

    it('can list pending loans', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Loan::factory()->count(3)->pending()->create();
        Loan::factory()->approved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/loans/pending/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
    });

    it('can view loan details', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $loan = Loan::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/loans/{$loan->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user',
                    'equipment',
                    'status',
                    'requested_at',
                ],
            ]);
    });
});

describe('Loan Permissions', function () {

    it('usuario can create loans', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/loans', [
                'equipment_id' => $equipment->id,
                'expected_return_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(201);
    });

    it('colaborador can approve loans', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $loan = Loan::factory()->pending()->create([
            'equipment_id' => Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE])->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/approve");

        $response->assertStatus(200);
    });

    it('admin can approve loans', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN_PROGRAMADOR->value);

        $loan = Loan::factory()->pending()->create([
            'equipment_id' => Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE])->id,
        ]);

        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/loans/{$loan->id}/approve");

        $response->assertStatus(200);
    });
});
