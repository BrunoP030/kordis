<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NursingTaskRequest;
use App\Models\NursingTask;
use App\Presenters\NursingTaskPresenter;
use App\Services\NursingTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NursingTaskController extends Controller
{
    public function __construct(
        private readonly NursingTaskService $service,
        private readonly NursingTaskPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->presenter->paraQuadro($this->service->listarPorStatus($request->user())));
    }

    public function store(NursingTaskRequest $request): JsonResponse
    {
        return response()->json($this->presenter->paraCard($this->service->criar($request->validated())), 201);
    }

    public function atualizarStatus(Request $request, NursingTask $nursingTask): JsonResponse
    {
        // A equipe de limpeza só mexe nos cards de higienização.
        if ($request->user()->apenasLimpeza() && $nursingTask->categoria !== NursingTask::CATEGORIA_HIGIENIZACAO) {
            abort(403, 'Sem permissão para alterar esta tarefa.');
        }

        $dados = $request->validate([
            'status' => ['required', Rule::in(NursingTask::STATUS)],
        ], [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
        ]);

        $task = $this->service->atualizarStatus($nursingTask, $dados['status']);

        return response()->json($this->presenter->paraCard($task));
    }
}
