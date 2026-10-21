<?php

namespace App\Services;

use App\Models\Bed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BedService
{
    public function __construct(private readonly NursingTaskService $tarefas) {}

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
            // Aguardando higienização: o responsável é quem registrou a mudança.
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

        $statusAnterior = $leito->status;

        DB::transaction(fn () => $leito->update($dados + ['status' => $novoStatus]));

        // Interligação com o Kanban (o broadcast só acontece depois do commit).
        if ($novoStatus === 'limpeza' && $statusAnterior !== 'limpeza') {
            $this->tarefas->abrirHigienizacao($leito->codigo);
        } elseif ($statusAnterior === 'limpeza' && $novoStatus !== 'limpeza') {
            $this->tarefas->concluirHigienizacao($leito->codigo);
        }

        return $leito->refresh()->load('responsavel');
    }
}
