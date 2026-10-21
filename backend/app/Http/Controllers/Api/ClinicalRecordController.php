<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClinicalRecordRequest;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Presenters\ClinicalRecordPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicalRecordController extends Controller
{
    public function __construct(private readonly ClinicalRecordPresenter $presenter) {}

    /** Pacientes para o seletor do prontuário (acessível a quem tem prontuarios.visualizar). */
    public function pacientes(Request $request): JsonResponse
    {
        $busca = trim((string) $request->query('busca', ''));

        $pacientes = Patient::query()
            ->when($busca !== '', fn ($q) => $q->where('nome_completo', 'ilike', "%{$busca}%"))
            ->orderBy('nome_completo')
            ->limit(50)
            ->get();

        return response()->json($this->presenter->paraPacientes($pacientes));
    }

    public function index(Patient $patient): JsonResponse
    {
        $registros = $patient->clinicalRecords()->with('autor')->latest()->latest('id')->get();

        return response()->json([
            'Paciente' => $this->presenter->paraPaciente($patient),
            'Registros' => $this->presenter->paraHistorico($registros),
        ]);
    }

    public function store(ClinicalRecordRequest $request, Patient $patient): JsonResponse
    {
        $registro = $patient->clinicalRecords()->create($request->validated() + ['autor_id' => $request->user()->id]);

        return response()->json($this->presenter->paraRegistro($registro->load('autor')), 201);
    }
}
