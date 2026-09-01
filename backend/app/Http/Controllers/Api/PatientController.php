<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Presenters\PatientPresenter;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $service,
        private readonly PatientPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->listar($request->query('busca'), (int) $request->query('porPagina', 20));
        return response()->json($this->presenter->paraGrid($paginator));
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json($this->presenter->paraDetalhe($patient));
    }

    public function store(PatientRequest $request): JsonResponse
    {
        $paciente = $this->service->criar($request->validated());
        return response()->json($this->presenter->paraDetalhe($paciente), 201);
    }

    public function update(PatientRequest $request, Patient $patient): JsonResponse
    {
        $paciente = $this->service->atualizar($patient, $request->validated());
        return response()->json($this->presenter->paraDetalhe($paciente));
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $this->service->excluir($patient);
        return response()->json(null, 204);
    }
}
