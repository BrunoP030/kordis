<?php

namespace App\Presenters;

use App\Models\NursingTask;
use Illuminate\Support\Collection;

class NursingTaskPresenter
{
    /**
     * Itens no formato esperado pelo ej2-vue-kanban (keyField = Status).
     *
     * @param array<string, Collection<int, NursingTask>> $agrupadas
     */
    public function paraQuadro(array $agrupadas): array
    {
        return collect($agrupadas)
            ->flatten(1)
            ->map(fn (NursingTask $t) => $this->paraCard($t))
            ->values()
            ->all();
    }

    public function paraCard(NursingTask $task): array
    {
        return [
            'Id' => $task->id,
            'Status' => $task->status,
            'Titulo' => $task->titulo,
            'Subtitulo' => $this->subtitulo($task),
            'CorBorda' => $this->corBorda($task),
            'Concluido' => $task->status === 'concluido',
        ];
    }

    private function subtitulo(NursingTask $task): string
    {
        $partes = array_filter([$task->paciente_nome, $task->descricao]);

        return $partes ? implode(' • ', $partes) : ($task->leito ? "Leito {$task->leito}" : '');
    }

    private function corBorda(NursingTask $task): string
    {
        return match (true) {
            $task->status === 'concluido' => 'var(--kordis-task-concluido)',
            $task->prioridade === 'urgente' => 'var(--kordis-task-urgente)',
            $task->status === 'em_andamento' => 'var(--kordis-task-progresso)',
            default => 'var(--kordis-navy-800)',
        };
    }
}
