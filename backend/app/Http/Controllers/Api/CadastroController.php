<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Tela "Cadastro": CRUD dos cadastros de apoio definidos em App\Cadastros\RegistroCadastros. */
class CadastroController extends Controller
{
    public function __construct(private readonly CadastroService $service) {}

    public function definicoes(): JsonResponse
    {
        return response()->json($this->service->definicoes());
    }

    public function index(string $recurso): JsonResponse
    {
        return response()->json($this->service->listar($recurso));
    }

    public function opcoes(Request $request, string $recurso): JsonResponse
    {
        $incluir = $request->integer('incluir') ?: null;

        return response()->json($this->service->opcoes($recurso, $incluir));
    }

    public function store(Request $request, string $recurso): JsonResponse
    {
        return response()->json($this->service->criar($recurso, $request->all()), 201);
    }

    public function update(Request $request, string $recurso, string $id): JsonResponse
    {
        return response()->json($this->service->atualizar($recurso, $id, $request->all()));
    }

    public function destroy(string $recurso, string $id): JsonResponse
    {
        $this->service->excluir($recurso, $id);

        return response()->json(null, 204);
    }
}
