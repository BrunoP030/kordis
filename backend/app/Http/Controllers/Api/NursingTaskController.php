<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function index(): JsonResponse
    {
        return response()->json($this->presenter->paraQuadro($this->service->listarPorStatus()));
    }

    public function atualizarStatus(Request $request, NursingTask $nursingTask): JsonResponse
    {
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
