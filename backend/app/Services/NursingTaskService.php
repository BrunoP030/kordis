<?php

namespace App\Services;

use App\Events\NursingTaskUpdated;
use App\Models\NursingTask;
use App\Models\User;
use App\Presenters\NursingTaskPresenter;
use Illuminate\Support\Collection;

class NursingTaskService
{
    public function __construct(private readonly NursingTaskPresenter $presenter) {}

    /**
     * @return array<string, Collection<int, NursingTask>> tasks agrupadas nas 3 colunas
     */
    public function listarPorStatus(?User $usuario = null): array
    {
        $tasks = NursingTask::query()
            // Equipe de limpeza só enxerga os cards de higienização.
            ->when($usuario?->apenasLimpeza(), fn ($q) => $q->where('categoria', NursingTask::CATEGORIA_HIGIENIZACAO))
            ->orderBy('id')
            ->get()
            ->groupBy('status');

        return collect(NursingTask::STATUS)
            ->mapWithKeys(fn (string $status) => [$status => $tasks->get($status, collect())])
            ->all();
    }

    public function criar(array $dados): NursingTask
    {
        return $this->publicar(NursingTask::create($dados + ['status' => 'a_fazer'])->refresh());
    }

    public function atualizarStatus(NursingTask $task, string $novoStatus): NursingTask
    {
        $task->update([
            'status' => $novoStatus,
            'concluido_em' => $novoStatus === 'concluido' ? now() : null,
        ]);

        return $this->publicar($task->refresh());
    }

    /** Abre (na coluna "Pendente") a tarefa de higienização do leito, sem duplicar se já houver uma aberta. */
    public function abrirHigienizacao(string $codigoLeito): NursingTask
    {
        return $this->higienizacaoAberta($codigoLeito)
            ?? $this->criar([
                'titulo' => "Higienização {$codigoLeito}",
                'descricao' => 'Leito aguardando higienização',
                'prioridade' => 'normal',
                'categoria' => NursingTask::CATEGORIA_HIGIENIZACAO,
                'leito' => $codigoLeito,
            ]);
    }

    /** Leito liberado por outro caminho: encerra a tarefa de higienização que ficou aberta. */
    public function concluirHigienizacao(string $codigoLeito): void
    {
        if ($task = $this->higienizacaoAberta($codigoLeito)) {
            $this->atualizarStatus($task, 'concluido');
        }
    }

    private function higienizacaoAberta(string $codigoLeito): ?NursingTask
    {
        return NursingTask::query()
            ->where('categoria', NursingTask::CATEGORIA_HIGIENIZACAO)
            ->where('leito', $codigoLeito)
            ->where('status', '!=', 'concluido')
            ->first();
    }

    private function publicar(NursingTask $task): NursingTask
    {
        // O dado já está salvo: se o WebSocket estiver fora, só registra o erro.
        try {
            NursingTaskUpdated::dispatch($this->presenter->paraCard($task), $task->categoria);
        } catch (\Throwable $e) {
            report($e);
        }

        return $task;
    }
}
