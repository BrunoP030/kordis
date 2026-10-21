<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\ClinicalRecordController;
use App\Http\Controllers\Api\NursingTaskController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\SurgicalBookingController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/patients', [PatientController::class, 'index'])->middleware('permission:pacientes.visualizar');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->middleware('permission:pacientes.visualizar');
    Route::post('/patients', [PatientController::class, 'store'])->middleware('permission:pacientes.criar');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->middleware('permission:pacientes.editar');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->middleware('permission:pacientes.excluir');

    Route::get('/surgical-bookings', [SurgicalBookingController::class, 'index'])->middleware('permission:centro-cirurgico.visualizar');
    Route::get('/surgical-rooms', [SurgicalBookingController::class, 'salas'])->middleware('permission:centro-cirurgico.visualizar');
    Route::middleware('permission:centro-cirurgico.gerenciar')->group(function () {
        Route::post('/surgical-bookings', [SurgicalBookingController::class, 'store']);
        Route::put('/surgical-bookings/{surgicalBooking}', [SurgicalBookingController::class, 'update']);
        Route::post('/surgical-bookings/{surgicalBooking}/iniciar', [SurgicalBookingController::class, 'iniciar']);
        Route::post('/surgical-bookings/{surgicalBooking}/finalizar', [SurgicalBookingController::class, 'finalizar']);
        Route::post('/surgical-bookings/{surgicalBooking}/liberar-sala', [SurgicalBookingController::class, 'liberarSala']);
        Route::post('/surgical-bookings/{surgicalBooking}/cancelar', [SurgicalBookingController::class, 'cancelar']);
        Route::post('/surgical-rooms', [SurgicalBookingController::class, 'storeSala']);
        Route::put('/surgical-rooms/{surgicalRoom}', [SurgicalBookingController::class, 'updateSala']);
    });

    Route::get('/beds', [BedController::class, 'index'])->middleware('permission:leitos.visualizar');
    Route::put('/beds/{bed}/status', [BedController::class, 'atualizarStatus'])->middleware('permission:leitos.gerenciar');

    Route::get('/nursing-tasks', [NursingTaskController::class, 'index'])->middleware('permission:dashboard.visualizar');
    Route::post('/nursing-tasks', [NursingTaskController::class, 'store'])->middleware('permission:tarefas.criar');
    Route::put('/nursing-tasks/{nursingTask}/status', [NursingTaskController::class, 'atualizarStatus'])->middleware('permission:dashboard.visualizar');

    Route::get('/clinical-records/patients', [ClinicalRecordController::class, 'pacientes'])->middleware('permission:prontuarios.visualizar');
    Route::get('/patients/{patient}/clinical-records', [ClinicalRecordController::class, 'index'])->middleware('permission:prontuarios.visualizar');
    Route::post('/patients/{patient}/clinical-records', [ClinicalRecordController::class, 'store'])->middleware('permission:prontuarios.criar');
});
