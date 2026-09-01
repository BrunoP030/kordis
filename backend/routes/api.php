<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
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
});
