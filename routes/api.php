<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Equipment\EquipmentController;
use App\Http\Controllers\Api\V1\Equipment\EquipmentMaintenanceController;
use App\Http\Controllers\Api\V1\Equipment\EquipmentTypeController;
use App\Http\Controllers\Api\V1\Equipment\EquipmentBrandController;
use App\Http\Controllers\Api\V1\Loans\LoanController;
use App\Http\Controllers\Api\V1\Reservations\ReservationController;
use App\Http\Controllers\Api\V1\Incidents\IncidentController;

/*
|--------------------------------------------------------------------------
| API Routes - SIGABE
|--------------------------------------------------------------------------
*/

// Health Check - Sin autenticación
Route::get('/health', HealthCheckController::class)
    ->name('health.check');

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Rutas de Autenticación - Públicas
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Login - Con rate limiting
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('auth.login');

        // Rutas protegidas con Sanctum
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');

            Route::post('/logout-all', [AuthController::class, 'logoutAll'])
                ->name('auth.logout-all');

            Route::get('/me', [AuthController::class, 'me'])
                ->name('auth.me');
        });

        // Register - Solo para administradores
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware(['auth:sanctum', 'permission:users.create'])
            ->name('auth.register');
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas Protegidas con Sanctum
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Módulo de Equipos
        |--------------------------------------------------------------------------
        */
        Route::prefix('equipment')->group(function () {
            // CRUD de equipos
            Route::get('/', [EquipmentController::class, 'index'])
                ->middleware('permission:equipment.view')
                ->name('equipment.index');

            Route::get('/{id}', [EquipmentController::class, 'show'])
                ->middleware('permission:equipment.view')
                ->name('equipment.show');

            Route::post('/', [EquipmentController::class, 'store'])
                ->middleware('permission:equipment.create')
                ->name('equipment.store');

            Route::put('/{id}', [EquipmentController::class, 'update'])
                ->middleware('permission:equipment.edit')
                ->name('equipment.update');

            Route::delete('/{id}', [EquipmentController::class, 'destroy'])
                ->middleware('permission:equipment.delete')
                ->name('equipment.destroy');

            // Acciones especiales
            Route::patch('/{id}/status', [EquipmentController::class, 'changeStatus'])
                ->middleware('permission:equipment.edit')
                ->name('equipment.change-status');

            Route::get('/{id}/availability', [EquipmentController::class, 'checkAvailability'])
                ->middleware('permission:equipment.view')
                ->name('equipment.check-availability');

            Route::get('/{id}/history', [EquipmentController::class, 'history'])
                ->middleware('permission:equipment.view')
                ->name('equipment.history');

            // Estadísticas
            Route::get('/stats/summary', [EquipmentController::class, 'statistics'])
                ->middleware('permission:reports.view')
                ->name('equipment.statistics');
        });

        /*
        |--------------------------------------------------------------------------
        | Mantenimientos de Equipos
        |--------------------------------------------------------------------------
        */
        Route::prefix('maintenances')->group(function () {
            Route::get('/', [EquipmentMaintenanceController::class, 'index'])
                ->middleware('permission:equipment.view')
                ->name('maintenances.index');

            Route::get('/{id}', [EquipmentMaintenanceController::class, 'show'])
                ->middleware('permission:equipment.view')
                ->name('maintenances.show');

            Route::post('/', [EquipmentMaintenanceController::class, 'store'])
                ->middleware('permission:equipment.maintenance')
                ->name('maintenances.store');

            Route::put('/{id}', [EquipmentMaintenanceController::class, 'update'])
                ->middleware('permission:equipment.maintenance')
                ->name('maintenances.update');

            // Acciones de mantenimiento
            Route::post('/{id}/start', [EquipmentMaintenanceController::class, 'start'])
                ->middleware('permission:equipment.maintenance')
                ->name('maintenances.start');

            Route::post('/{id}/complete', [EquipmentMaintenanceController::class, 'complete'])
                ->middleware('permission:equipment.maintenance')
                ->name('maintenances.complete');

            Route::post('/{id}/cancel', [EquipmentMaintenanceController::class, 'cancel'])
                ->middleware('permission:equipment.maintenance')
                ->name('maintenances.cancel');

            // Vistas especiales
            Route::get('/upcoming/list', [EquipmentMaintenanceController::class, 'upcoming'])
                ->middleware('permission:equipment.view')
                ->name('maintenances.upcoming');

            Route::get('/overdue/list', [EquipmentMaintenanceController::class, 'overdue'])
                ->middleware('permission:equipment.view')
                ->name('maintenances.overdue');

            Route::get('/stats/summary', [EquipmentMaintenanceController::class, 'statistics'])
                ->middleware('permission:reports.view')
                ->name('maintenances.statistics');
        });

        /*
        |--------------------------------------------------------------------------
        | Tipos de Equipos
        |--------------------------------------------------------------------------
        */
        Route::prefix('equipment-types')->group(function () {
            Route::get('/', [EquipmentTypeController::class, 'index'])
                ->name('equipment-types.index');

            Route::get('/{id}', [EquipmentTypeController::class, 'show'])
                ->name('equipment-types.show');

            Route::post('/', [EquipmentTypeController::class, 'store'])
                ->middleware('permission:settings.edit')
                ->name('equipment-types.store');

            Route::put('/{id}', [EquipmentTypeController::class, 'update'])
                ->middleware('permission:settings.edit')
                ->name('equipment-types.update');

            Route::delete('/{id}', [EquipmentTypeController::class, 'destroy'])
                ->middleware('permission:settings.edit')
                ->name('equipment-types.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Marcas de Equipos
        |--------------------------------------------------------------------------
        */
        Route::prefix('equipment-brands')->group(function () {
            Route::get('/', [EquipmentBrandController::class, 'index'])
                ->name('equipment-brands.index');

            Route::get('/{id}', [EquipmentBrandController::class, 'show'])
                ->name('equipment-brands.show');

            Route::post('/', [EquipmentBrandController::class, 'store'])
                ->middleware('permission:settings.edit')
                ->name('equipment-brands.store');

            Route::put('/{id}', [EquipmentBrandController::class, 'update'])
                ->middleware('permission:settings.edit')
                ->name('equipment-brands.update');

            Route::delete('/{id}', [EquipmentBrandController::class, 'destroy'])
                ->middleware('permission:settings.edit')
                ->name('equipment-brands.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Módulo de Préstamos
        |--------------------------------------------------------------------------
        */
        Route::prefix('loans')->group(function () {
            // Listar préstamos
            Route::get('/', [LoanController::class, 'index'])
                ->middleware('permission:loans.view')
                ->name('loans.index');

            // Ver detalle de préstamo
            Route::get('/{id}', [LoanController::class, 'show'])
                ->middleware('permission:loans.view')
                ->name('loans.show');

            // Crear solicitud de préstamo
            Route::post('/', [LoanController::class, 'store'])
                ->middleware('permission:loans.create')
                ->name('loans.store');

            // Aprobar préstamo
            Route::post('/{id}/approve', [LoanController::class, 'approve'])
                ->middleware('permission:loans.approve')
                ->name('loans.approve');

            // Rechazar préstamo
            Route::post('/{id}/reject', [LoanController::class, 'reject'])
                ->middleware('permission:loans.approve')
                ->name('loans.reject');

            // Devolver equipo
            Route::post('/{id}/return', [LoanController::class, 'return'])
                ->middleware('permission:loans.return')
                ->name('loans.return');

            // Vistas especiales
            Route::get('/me/active', [LoanController::class, 'myLoans'])
                ->name('loans.my-loans');

            Route::get('/pending/list', [LoanController::class, 'pending'])
                ->middleware('permission:loans.approve')
                ->name('loans.pending');

            Route::get('/overdue/list', [LoanController::class, 'overdue'])
                ->middleware('permission:loans.view')
                ->name('loans.overdue');

            // Estadísticas
            Route::get('/stats/summary', [LoanController::class, 'statistics'])
                ->middleware('permission:loans.view')
                ->name('loans.statistics');
        });

        /*
        |--------------------------------------------------------------------------
        | Módulo de Reservas
        |--------------------------------------------------------------------------
        */
        Route::prefix('reservations')->group(function () {
            // Listar reservas
            Route::get('/', [ReservationController::class, 'index'])
                ->middleware('permission:reservations.view')
                ->name('reservations.index');

            // Ver detalle de reserva
            Route::get('/{id}', [ReservationController::class, 'show'])
                ->middleware('permission:reservations.view')
                ->name('reservations.show');

            // Crear solicitud de reserva
            Route::post('/', [ReservationController::class, 'store'])
                ->middleware('permission:reservations.create')
                ->name('reservations.store');

            // Aprobar reserva
            Route::post('/{id}/approve', [ReservationController::class, 'approve'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.approve');

            // Rechazar reserva
            Route::post('/{id}/reject', [ReservationController::class, 'reject'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.reject');

            // Cancelar reserva
            Route::post('/{id}/cancel', [ReservationController::class, 'cancel'])
                ->name('reservations.cancel');

            // Activar reserva
            Route::post('/{id}/activate', [ReservationController::class, 'activate'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.activate');

            // Completar reserva
            Route::post('/{id}/complete', [ReservationController::class, 'complete'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.complete');

            // Convertir a préstamo
            Route::post('/{id}/convert-to-loan', [ReservationController::class, 'convertToLoan'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.convert-to-loan');

            // Eliminar reserva
            Route::delete('/{id}', [ReservationController::class, 'destroy'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.destroy');

            // Vistas especiales
            Route::get('/me/list', [ReservationController::class, 'myReservations'])
                ->name('reservations.my-reservations');

            Route::get('/pending/list', [ReservationController::class, 'pending'])
                ->middleware('permission:reservations.approve')
                ->name('reservations.pending');
        });

        /*
        |--------------------------------------------------------------------------
        | Módulo de Incidentes
        |--------------------------------------------------------------------------
        */
        Route::prefix('incidents')->group(function () {
            // Listar incidentes
            Route::get('/', [IncidentController::class, 'index'])
                ->middleware('permission:incidents.view')
                ->name('incidents.index');

            // Ver detalle de incidente
            Route::get('/{id}', [IncidentController::class, 'show'])
                ->middleware('permission:incidents.view')
                ->name('incidents.show');

            // Crear incidente
            Route::post('/', [IncidentController::class, 'store'])
                ->middleware('permission:incidents.create')
                ->name('incidents.store');

            // Asignar incidente
            Route::post('/{id}/assign', [IncidentController::class, 'assign'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.assign');

            // Desasignar incidente
            Route::post('/{id}/unassign', [IncidentController::class, 'unassign'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.unassign');

            // Iniciar reparación
            Route::post('/{id}/start-repair', [IncidentController::class, 'startRepair'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.start-repair');

            // Resolver incidente
            Route::post('/{id}/resolve', [IncidentController::class, 'resolve'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.resolve');

            // Cerrar incidente
            Route::post('/{id}/close', [IncidentController::class, 'close'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.close');

            // Reabrir incidente
            Route::post('/{id}/reopen', [IncidentController::class, 'reopen'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.reopen');

            // Eliminar incidente
            Route::delete('/{id}', [IncidentController::class, 'destroy'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.destroy');

            // Vistas especiales
            Route::get('/active/list', [IncidentController::class, 'active'])
                ->middleware('permission:incidents.view')
                ->name('incidents.active');

            Route::get('/me/list', [IncidentController::class, 'myIncidents'])
                ->name('incidents.my-incidents');

            Route::get('/assigned-to-me/list', [IncidentController::class, 'assignedToMe'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.assigned-to-me');

            Route::get('/unassigned/list', [IncidentController::class, 'unassigned'])
                ->middleware('permission:incidents.resolve')
                ->name('incidents.unassigned');

            // Estadísticas
            Route::get('/stats/summary', [IncidentController::class, 'statistics'])
                ->middleware('permission:incidents.view')
                ->name('incidents.statistics');
        });
    });
});

/*
|--------------------------------------------------------------------------
| External API Routes - API Pública con API Key
|--------------------------------------------------------------------------
*/
Route::prefix('external/v1')->middleware(['api.key', 'throttle:external-api'])->group(function () {

    // Equipos públicos
    Route::get('/equipment', [EquipmentController::class, 'index'])
        ->middleware('api.key:equipment')
        ->name('external.equipment.index');

    Route::get('/equipment/{id}', [EquipmentController::class, 'show'])
        ->middleware('api.key:equipment')
        ->name('external.equipment.show');

    Route::get('/equipment/{id}/availability', [EquipmentController::class, 'checkAvailability'])
        ->middleware('api.key:equipment')
        ->name('external.equipment.availability');

    // Tipos de equipos
    Route::get('/equipment-types', [EquipmentTypeController::class, 'index'])
        ->middleware('api.key:equipment')
        ->name('external.equipment-types.index');
});
