<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PatientService
{
    public function listar(?string $busca, int $porPagina = 20): LengthAwarePaginator
    {
        return Patient::query()
            ->when($busca, function ($query, $busca) {
                $query->where('nome_completo', 'ilike', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%")
                    ->orWhere('cartao_sus', 'like', "%{$busca}%");
            })
            ->orderBy('nome_completo')
            ->paginate($porPagina);
    }

    public function criar(array $dados): Patient
    {
        $dados['criado_por'] = Auth::id();
        return Patient::create($dados);
    }

    public function atualizar(Patient $paciente, array $dados): Patient
    {
        $paciente->update($dados);
        return $paciente->refresh();
    }

    public function excluir(Patient $paciente): void
    {
        $paciente->delete();
    }
}
