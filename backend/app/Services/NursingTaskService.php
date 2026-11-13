<?php

namespace App\Services;

use App\Events\NursingTaskUpdated;
use App\Models\Bed;
use App\Models\Categoria;
use App\Models\NursingTask;
use App\Models\Prioridade;
use App\Models\User;
use App\Presenters\NursingTaskPresenter;
use Illuminate\Support\Collection;

class NursingTaskService
{
    private const RELACOES = ['prioridade', 'categoria', 'paciente', 'leito'];

    public function __construct(private readonly NursingTaskPresenter $presenter) {}

    /**
     * @return array<string, Collection<int, NursingTask>> tasks agrupadas nas 3 colunas
     */
    public function listarPorStatus(?User $usuario = null): array
    {
        $tasks = NursingTask::query()
            ->with(self::RELACOES)
            // Equipe de limpeza só enxerga os cards de higienização.
            ->when($usuario?->apenasLimpeza(), fn ($q) => $q->higienizacao())
            ->orderBy('id')
            ->get()
            ->groupBy('status');

        return collect(NursingTask::STATUS)
            ->mapWithKeys(fn (string $status) => [$status => $tasks->get($status, collect())])
            ->all();
    }

    public function criar(array $dados): NursingTask
    {
        return $this->publicar(NursingTask::create($dados + ['status' => 'a_fazer']));
    }

    public function atualizarStatus(NursingTask $task, string $novoStatus): NursingTask
    {
        $task->update([
            'status' => $novoStatus,
            'concluido_em' => $novoStatus === 'concluido' ? now() : null,
        ]);

        return $this->publicar($task);
    }

    /** Abre (na coluna "Pendente") a tarefa de higienização do leito, sem duplicar se já houver uma aberta. */
    public function abrirHigienizacao(Bed $leito): NursingTask
    {
        return $this->higienizacaoAberta($leito)
            ?? $this->criar([
                'titulo' => "Higienização {$leito->codigo}",
                'descricao' => 'Leito aguardando higienização',
                'prioridade_id' => Prioridade::where('nome', 'Normal')->value('id'),
                'categoria_id' => Categoria::idPorNome(Categoria::HIGIENIZACAO),
                'leito_id' => $leito->id,
            ]);
    }

    /** Leito liberado por outro caminho: encerra a tarefa de higienização que ficou aberta. */
    public function concluirHigienizacao(Bed $leito): void
    {
        if ($task = $this->higienizacaoAberta($leito)) {
            $this->atualizarStatus($task, 'concluido');
        }
    }

    private function higienizacaoAberta(Bed $leito): ?NursingTask
    {
        return NursingTask::query()
            ->higienizacao()
            ->where('leito_id', $leito->id)
            ->where('status', '!=', 'concluido')
            ->first();
    }

    private function publicar(NursingTask $task): NursingTask
    {
        $task->refresh()->load(self::RELACOES);

        // O dado já está salvo: se o WebSocket estiver fora, só registra o erro.
        try {
            NursingTaskUpdated::dispatch($this->presenter->paraCard($task), (string) $task->categoria?->nome);
        } catch (\Throwable $e) {
            report($e);
        }

        return $task;
    }
}
