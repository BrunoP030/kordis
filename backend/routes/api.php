<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BedController;
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
    Route::post('/surgical-bookings', [SurgicalBookingController::class, 'store'])->middleware('permission:centro-cirurgico.gerenciar');

    Route::get('/beds', [BedController::class, 'index'])->middleware('permission:leitos.visualizar');
    Route::put('/beds/{bed}/status', [BedController::class, 'atualizarStatus'])->middleware('permission:leitos.gerenciar');

    Route::get('/nursing-tasks', [NursingTaskController::class, 'index'])->middleware('permission:dashboard.visualizar');
    Route::put('/nursing-tasks/{nursingTask}/status', [NursingTaskController::class, 'atualizarStatus'])->middleware('permission:dashboard.visualizar');
});
