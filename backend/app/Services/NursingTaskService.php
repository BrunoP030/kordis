<?php

namespace App\Services;

use App\Models\NursingTask;
use Illuminate\Support\Collection;

class NursingTaskService
{
    /**
     * @return array<string, Collection<int, NursingTask>> tasks agrupadas nas 3 colunas
     */
    public function listarPorStatus(): array
    {
        $tasks = NursingTask::query()->orderBy('id')->get()->groupBy('status');

        return collect(NursingTask::STATUS)
            ->mapWithKeys(fn (string $status) => [$status => $tasks->get($status, collect())])
            ->all();
    }

    public function atualizarStatus(NursingTask $task, string $novoStatus): NursingTask
    {
        $task->update([
            'status' => $novoStatus,
            'concluido_em' => $novoStatus === 'concluido' ? now() : null,
        ]);

        return $task->refresh();
    }
}
