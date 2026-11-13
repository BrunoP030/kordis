<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Categoria;
use App\Models\Especialidade;
use App\Models\Patient;
use App\Models\Prioridade;
use App\Models\TipoProntuario;
use App\Support\SimNao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Listas de apoio (cadastros) para preencher os seletores das telas. */
class OpcoesController extends Controller
{
    public function index(): JsonResponse
    {
        $opcao = fn ($m) => ['Id' => $m->id, 'Nome' => $m->nome];

        return response()->json([
            'Prioridades' => Prioridade::orderBy('id')->get()->map($opcao),
            'Categorias' => Categoria::orderBy('id')->get()->map($opcao),
            'Especialidades' => Especialidade::orderBy('nome')->get()->map($opcao),
            'TiposProntuario' => TipoProntuario::where('status', SimNao::SIM)->orderBy('id')->get()->map($opcao),
            'Leitos' => Bed::orderBy('codigo')->get()->map(fn (Bed $l) => ['Id' => $l->id, 'Nome' => $l->codigo, 'Status' => $l->status]),
        ]);
    }

    /** Pacientes para os seletores de leito e de tarefa (busca opcional por nome ou CPF). */
    public function pacientes(Request $request): JsonResponse
    {
        $busca = trim((string) $request->query('busca', ''));

        $pacientes = Patient::query()
            ->whereNot('status', 'obito')
            ->when($busca !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nome_completo', 'ilike', "%{$busca}%")
                ->orWhere('cpf', 'like', "%{$busca}%")))
            ->orderBy('nome_completo')
            ->limit(100)
            ->get();

        return response()->json($pacientes->map(fn (Patient $p) => ['Id' => $p->id, 'Nome' => $p->nome_completo]));
    }
}
