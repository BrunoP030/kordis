<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BedStatusRequest;
use App\Models\Bed;
use App\Presenters\BedPresenter;
use App\Services\BedService;
use Illuminate\Http\JsonResponse;

class BedController extends Controller
{
    public function __construct(
        private readonly BedService $service,
        private readonly BedPresenter $presenter,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->presenter->paraGrid($this->service->listar()));
    }

    public function atualizarStatus(BedStatusRequest $request, Bed $bed): JsonResponse
    {
        $dados = $request->validated();
        $leito = $this->service->atualizarStatus($bed, $dados['status'], $dados);

        return response()->json($this->presenter->paraLinhaGrid($leito));
    }
}
