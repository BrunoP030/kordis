<?php

namespace App\Services;

use App\Models\Bed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BedService
{
    public function listar(): Collection
    {
        return Bed::query()
            ->with('responsavel')
            ->orderByRaw("case status when 'ocupado' then 1 when 'limpeza' then 2 else 3 end")
            ->orderBy('codigo')
            ->get();
    }

    public function atualizarStatus(Bed $leito, string $novoStatus, array $dadosExtras = []): Bed
    {
        $dados = match ($novoStatus) {
            'ocupado' => [
                'paciente_nome' => $dadosExtras['paciente_nome'],
                'entrada_em' => $dadosExtras['entrada_em'],
                'previsao_alta' => $dadosExtras['previsao_alta'] ?? null,
                'responsavel_id' => null,
            ],
            // Em limpeza, o responsável é quem registrou a mudança.
            'limpeza' => [
                'paciente_nome' => null,
                'entrada_em' => null,
                'previsao_alta' => null,
                'responsavel_id' => Auth::id(),
            ],
            default => [
                'paciente_nome' => null,
                'entrada_em' => null,
                'previsao_alta' => null,
                'responsavel_id' => null,
            ],
        };

        $leito->update($dados + ['status' => $novoStatus]);

        return $leito->refresh()->load('responsavel');
    }
}
