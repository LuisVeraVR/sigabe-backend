<?php

declare(strict_types=1);

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Incidents\Enums\IncidentPriority;
use App\Domain\Incidents\Enums\IncidentStatus;
use App\Domain\Incidents\Models\Incident;
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

describe('Incident CRUD', function () {

    it('usuario can create incident', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/incidents', [
                'equipment_id' => $equipment->id,
                'title' => 'El equipo no enciende',
                'description' => 'Al presionar el botón de encendido, el equipo no responde',
                'priority' => 'alta',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status.value', 'reportado')
            ->assertJsonPath('data.reported_by.id', $usuario->id)
            ->assertJsonPath('data.equipment.id', $equipment->id)
            ->assertJsonPath('data.priority.value', 'alta');

        $this->assertDatabaseHas('incidents', [
            'equipment_id' => $equipment->id,
            'reported_by' => $usuario->id,
            'title' => 'El equipo no enciende',
            'status' => IncidentStatus::REPORTADO->value,
            'priority' => IncidentPriority::ALTA->value,
        ]);
    });

    it('creates incident with default medium priority', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/incidents', [
                'equipment_id' => $equipment->id,
                'title' => 'Test incident',
                'description' => 'Test description',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.priority.value', 'media');
    });

    it('validates required fields', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/incidents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id', 'title', 'description']);
    });

    it('validates priority is valid enum value', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/incidents', [
                'equipment_id' => $equipment->id,
                'title' => 'Test',
                'description' => 'Test description',
                'priority' => 'invalid_priority',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    });

    it('can list incidents with filters', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Incident::factory()->count(3)->reportado()->create();
        Incident::factory()->count(2)->resuelto()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/incidents');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
    });

    it('can view incident details', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/incidents/{$incident->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'equipment',
                    'reported_by',
                    'title',
                    'description',
                    'status',
                    'priority',
                ],
            ]);
    });
});

describe('Incident Assignment', function () {

    it('colaborador can assign incident to technician', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $technician = User::factory()->create();
        $technician->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/assign", [
                'assigned_to' => $technician->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'en_revision')
            ->assertJsonPath('data.assigned_to.id', $technician->id);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'assigned_to' => $technician->id,
            'status' => IncidentStatus::EN_REVISION->value,
        ]);
    });

    it('usuario cannot assign incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $technician = User::factory()->create();
        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/assign", [
                'assigned_to' => $technician->id,
            ]);

        $response->assertStatus(403);
    });

    it('colaborador can unassign incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->enRevision()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/unassign");

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_to', null)
            ->assertJsonPath('data.status.value', 'reportado');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'assigned_to' => null,
            'status' => IncidentStatus::REPORTADO->value,
        ]);
    });

    it('cannot assign closed incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $technician = User::factory()->create();
        $incident = Incident::factory()->cerrado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/assign", [
                'assigned_to' => $technician->id,
            ]);

        $response->assertStatus(400);
    });

    it('validates assigned_to is required', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/assign", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    });
});

describe('Incident Workflow - Start Repair', function () {

    it('colaborador can start repair on assigned incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->enRevision()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/start-repair");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'en_reparacion');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => IncidentStatus::EN_REPARACION->value,
        ]);
    });

    it('usuario cannot start repair', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->enRevision()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/start-repair");

        $response->assertStatus(403);
    });

    it('cannot start repair on reportado incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/start-repair");

        $response->assertStatus(400);
    });

    it('cannot start repair on already resolved incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->resuelto()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/start-repair");

        $response->assertStatus(400);
    });
});

describe('Incident Workflow - Resolve', function () {

    it('colaborador can resolve incident in repair', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->enReparacion()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/resolve", [
                'resolution_notes' => 'Se reemplazó el cable de alimentación defectuoso',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'resuelto')
            ->assertJsonPath('data.resolution_notes', 'Se reemplazó el cable de alimentación defectuoso');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => IncidentStatus::RESUELTO->value,
            'resolution_notes' => 'Se reemplazó el cable de alimentación defectuoso',
        ]);

        // Verificar que resolved_at fue registrado
        $incident->refresh();
        expect($incident->resolved_at)->not->toBeNull();
    });

    it('usuario cannot resolve incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->enReparacion()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/resolve", [
                'resolution_notes' => 'Test notes',
            ]);

        $response->assertStatus(403);
    });

    it('requires resolution notes', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->enReparacion()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/resolve", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resolution_notes']);
    });

    it('cannot resolve incident not in repair', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/resolve", [
                'resolution_notes' => 'Test notes',
            ]);

        $response->assertStatus(400);
    });
});

describe('Incident Workflow - Close', function () {

    it('colaborador can close resolved incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->resuelto()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/close");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'cerrado');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => IncidentStatus::CERRADO->value,
        ]);

        // Verificar que closed_at fue registrado
        $incident->refresh();
        expect($incident->closed_at)->not->toBeNull();
    });

    it('usuario cannot close incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->resuelto()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/close");

        $response->assertStatus(403);
    });

    it('cannot close incident that is not resolved', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->enReparacion()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/close");

        $response->assertStatus(400);
    });

    it('cannot close already closed incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->cerrado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/close");

        $response->assertStatus(400);
    });
});

describe('Incident Workflow - Reopen', function () {

    it('colaborador can reopen closed incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->cerrado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/reopen");

        $response->assertStatus(200)
            ->assertJsonPath('data.status.value', 'reportado');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => IncidentStatus::REPORTADO->value,
            'assigned_to' => null,
        ]);

        // Verificar que closed_at y resolved_at fueron reseteados
        $incident->refresh();
        expect($incident->closed_at)->toBeNull();
        expect($incident->resolved_at)->toBeNull();
    });

    it('usuario cannot reopen incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->cerrado()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/reopen");

        $response->assertStatus(403);
    });

    it('cannot reopen incident that is not closed', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->resuelto()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/reopen");

        $response->assertStatus(400);
    });
});

describe('Incident Listing', function () {

    it('can list all incidents', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Incident::factory()->count(5)->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/incidents');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
    });

    it('can list my incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        // Incidentes del usuario
        Incident::factory()->count(2)->create(['reported_by' => $usuario->id]);

        // Incidentes de otros usuarios
        Incident::factory()->count(3)->create();

        $response = actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/incidents/me/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);
    });

    it('can list incidents assigned to me', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        // Incidentes asignados al colaborador
        Incident::factory()->count(3)->enRevision()->create(['assigned_to' => $colaborador->id]);

        // Incidentes asignados a otros
        Incident::factory()->count(2)->enRevision()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/incidents/assigned-to-me/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
    });

    it('can list unassigned incidents', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        // Incidentes sin asignar
        Incident::factory()->count(4)->reportado()->unassigned()->create();

        // Incidentes asignados
        Incident::factory()->count(2)->enRevision()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/incidents/unassigned/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(4);
    });

    it('can list active incidents', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        // Incidentes activos (reportado, en_revision, en_reparacion)
        Incident::factory()->reportado()->create();
        Incident::factory()->count(2)->enRevision()->create();
        Incident::factory()->enReparacion()->create();

        // Incidentes cerrados
        Incident::factory()->resuelto()->create();
        Incident::factory()->cerrado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/incidents/active/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(4);
    });
});

describe('Incident Statistics', function () {

    it('colaborador can view statistics', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        Incident::factory()->count(3)->reportado()->create();
        Incident::factory()->count(2)->resuelto()->create();
        Incident::factory()->cerrado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->getJson('/api/v1/incidents/stats/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'by_status',
                    'by_priority',
                    'active',
                ],
            ]);
    });

    it('usuario cannot view statistics', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $response = actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/incidents/stats/summary');

        $response->assertStatus(403);
    });
});

describe('Incident Deletion', function () {

    it('colaborador can delete incident', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->deleteJson("/api/v1/incidents/{$incident->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('incidents', [
            'id' => $incident->id,
        ]);
    });

    it('usuario cannot delete incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->reportado()->create();

        $response = actingAs($usuario, 'sanctum')
            ->deleteJson("/api/v1/incidents/{$incident->id}");

        $response->assertStatus(403);
    });
});

describe('Incident Permissions', function () {

    it('usuario can view incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $incident = Incident::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->getJson("/api/v1/incidents/{$incident->id}");

        $response->assertStatus(200);
    });

    it('usuario can create incidents', function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(UserRole::USUARIO->value);

        $equipment = Equipment::factory()->create();

        $response = actingAs($usuario, 'sanctum')
            ->postJson('/api/v1/incidents', [
                'equipment_id' => $equipment->id,
                'title' => 'Test incident',
                'description' => 'Test description',
            ]);

        $response->assertStatus(201);
    });

    it('colaborador can resolve incidents', function () {
        $colaborador = User::factory()->create();
        $colaborador->assignRole(UserRole::COLABORADOR->value);

        $incident = Incident::factory()->enReparacion()->create();

        $response = actingAs($colaborador, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/resolve", [
                'resolution_notes' => 'Fixed successfully',
            ]);

        $response->assertStatus(200);
    });

    it('admin can perform all actions', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN_PROGRAMADOR->value);

        $technician = User::factory()->create();
        $incident = Incident::factory()->reportado()->create();

        // Assign
        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/assign", [
                'assigned_to' => $technician->id,
            ]);

        $response->assertStatus(200);

        // Start repair
        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/start-repair");

        $response->assertStatus(200);

        // Resolve
        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/resolve", [
                'resolution_notes' => 'Admin resolved',
            ]);

        $response->assertStatus(200);

        // Close
        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/incidents/{$incident->id}/close");

        $response->assertStatus(200);
    });
});
