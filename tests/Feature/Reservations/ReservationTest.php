<?php

declare(strict_types=1);

use App\Domain\Equipment\Enums\EquipmentStatus;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Reservations\Enums\ReservationStatus;
use App\Domain\Reservations\Models\Reservation;
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

describe('Reservation CRUD', function () {

    it('usuario can create reservation request', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'notes' => 'Necesito este equipo para un evento',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.user.id', $usuario->id)
            ->assertJsonPath('data.equipment.id', $equipment->id);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $usuario->id,
            'equipment_id' => $equipment->id,
            'status' => ReservationStatus::PENDING->value,
        ]);
    });

    it('cannot create reservation with conflicting dates', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        // Crear reserva existente
        Reservation::factory()->approved()->create([
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        // Intentar crear reserva conflictiva
        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->addDays(6)->toDateString(),
                'end_date' => now()->addDays(8)->toDateString(),
            ]);

        $response->assertStatus(400);
    });

    it('cannot create reservation if user has 3 active reservations', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        // Crear 3 reservas activas
        Reservation::factory()->count(3)->approved()->create([
            'user_id' => $usuario->id,
        ]);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(400);
    });

    it('cannot create reservation with start date in the past', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    });

    it('cannot create reservation with end date before start date', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    });

    it('cannot create reservation exceeding 30 days', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(35)->toDateString(),
            ]);

        $response->assertStatus(400);
    });

    it('validates required fields', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id', 'start_date', 'end_date']);
    });
});

describe('Reservation Approval', function () {

    it('colaborador can approve reservation', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->pending()->create([
            'equipment_id' => Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE])->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'approved');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::APPROVED->value,
            'approved_by' => $colaborador->id,
        ]);
    });

    it('usuario cannot approve reservations', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->pending()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/approve");

        $response->assertStatus(403);
    });

    it('cannot approve already approved reservation', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->approved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/approve");

        $response->assertStatus(400);
    });

    it('cannot approve reservation with conflicting dates', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        // Crear reserva aprobada existente
        Reservation::factory()->approved()->create([
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        // Crear reserva pendiente con conflicto
        $reservation = Reservation::factory()->pending()->create([
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDays(6),
            'end_date' => now()->addDays(8),
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/approve");

        $response->assertStatus(400);
    });
});

describe('Reservation Rejection', function () {

    it('colaborador can reject reservation', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->pending()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/reject", [
                'rejection_reason' => 'Equipo no disponible para estas fechas',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'rejected');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::REJECTED->value,
            'rejection_reason' => 'Equipo no disponible para estas fechas',
        ]);
    });

    it('requires rejection reason', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->pending()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    });

    it('cannot reject already approved reservation', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->approved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/reject", [
                'rejection_reason' => 'Test reason',
            ]);

        $response->assertStatus(400);
    });
});

describe('Reservation Cancellation', function () {

    it('usuario can cancel their own pending reservation', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->pending()->create([
            'user_id' => $usuario->id,
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/cancel", [
                'cancellation_reason' => 'Ya no necesito el equipo',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'cancelled');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
            'cancellation_reason' => 'Ya no necesito el equipo',
        ]);
    });

    it('usuario can cancel their own approved reservation', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->approved()->create([
            'user_id' => $usuario->id,
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/cancel", [
                'cancellation_reason' => 'Cambio de planes',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'cancelled');
    });

    it('requires cancellation reason', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->pending()->create([
            'user_id' => $usuario->id,
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cancellation_reason']);
    });

    it('cannot cancel already completed reservation', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->completed()->create([
            'user_id' => $usuario->id,
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/cancel", [
                'cancellation_reason' => 'Test reason',
            ]);

        $response->assertStatus(400);
    });
});

describe('Reservation Activation', function () {

    it('colaborador can activate reservation on start date', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $reservation = Reservation::factory()->approved()->create([
            'equipment_id' => $equipment->id,
            'start_date' => now(),
            'end_date' => now()->addDays(3),
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'active');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::ACTIVE->value,
        ]);

        // Verificar que el equipo cambió de estado
        $this->assertDatabaseHas('equipment', [
            'id' => $reservation->equipment_id,
            'status' => EquipmentStatus::IN_USE->value,
        ]);
    });

    it('usuario cannot activate reservations', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->approved()->create([
            'start_date' => now(),
        ]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/activate");

        $response->assertStatus(403);
    });

    it('cannot activate reservation before start date', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->approved()->create([
            'start_date' => now()->addDays(5),
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/activate");

        $response->assertStatus(400);
    });
});

describe('Reservation Completion', function () {

    it('colaborador can complete active reservation', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::IN_USE]);

        $reservation = Reservation::factory()->active()->create([
            'equipment_id' => $equipment->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'completed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::COMPLETED->value,
        ]);

        // Verificar que el equipo volvió a disponible
        $this->assertDatabaseHas('equipment', [
            'id' => $reservation->equipment_id,
            'status' => EquipmentStatus::AVAILABLE->value,
        ]);
    });

    it('cannot complete reservation that is not active', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->pending()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/complete");

        $response->assertStatus(400);
    });
});

describe('Convert Reservation to Loan', function () {

    it('colaborador can convert approved reservation to loan', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $reservation = Reservation::factory()->approved()->create([
            'equipment_id' => $equipment->id,
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/convert-to-loan");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user',
                    'equipment',
                    'status',
                ],
            ]);

        // Verificar que se creó el préstamo
        $this->assertDatabaseHas('loans', [
            'user_id' => $reservation->user_id,
            'equipment_id' => $reservation->equipment_id,
            'status' => 'approved',
        ]);

        // Verificar que la reserva fue completada y tiene el loan_id
        $reservation->refresh();
        expect($reservation->status)->toBe(ReservationStatus::COMPLETED);
        expect($reservation->converted_loan_id)->not->toBeNull();
    });

    it('cannot convert reservation that is not on start date', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->approved()->create([
            'start_date' => now()->addDays(5),
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/convert-to-loan");

        $response->assertStatus(400);
    });

    it('cannot convert already converted reservation', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $reservation = Reservation::factory()->approved()->create([
            'equipment_id' => $equipment->id,
            'start_date' => now(),
        ]);

        // Convertir primera vez
        actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/convert-to-loan");

        // Intentar convertir nuevamente
        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/convert-to-loan");

        $response->assertStatus(400);
    });
});

describe('Reservation Listing', function () {

    it('can list reservations', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Reservation::factory()->count(3)->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/reservations');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
    });

    it('can list user reservations', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        Reservation::factory()->count(2)->approved()->create(['user_id' => $usuario->id]);
        Reservation::factory()->completed()->create(['user_id' => $usuario->id]);

        $response = actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/reservations/me/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
    });

    it('can list pending reservations', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Reservation::factory()->count(3)->pending()->create();
        Reservation::factory()->approved()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/reservations/pending/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
    });

    it('can view reservation details', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $reservation = Reservation::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/reservations/{$reservation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user',
                    'equipment',
                    'status',
                    'start_date',
                    'end_date',
                ],
            ]);
    });
});

describe('Reservation Permissions', function () {

    it('usuario can create reservations', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE]);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/reservations', [
                'equipment_id' => $equipment->id,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(201);
    });

    it('colaborador can approve reservations', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $reservation = Reservation::factory()->pending()->create([
            'equipment_id' => Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE])->id,
        ]);

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/approve");

        $response->assertStatus(200);
    });

    it('admin can approve reservations', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN_PROGRAMADOR->value);

        $reservation = Reservation::factory()->pending()->create([
            'equipment_id' => Equipment::factory()->create(['status' => EquipmentStatus::AVAILABLE])->id,
        ]);

        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/reservations/{$reservation->id}/approve");

        $response->assertStatus(200);
    });
});
