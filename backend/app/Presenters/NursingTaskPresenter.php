<?php

namespace App\Presenters;

use App\Models\NursingTask;
use App\Models\Prioridade;
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
            'CategoriaId' => $task->categoria_id,
            'Categoria' => $task->categoria?->nome,
            'PrioridadeId' => $task->prioridade_id,
            'Prioridade' => $task->prioridade?->nome,
            'LeitoId' => $task->leito_id,
            'Leito' => $task->leito?->codigo,
            'PacienteId' => $task->paciente_id,
            'Paciente' => $task->paciente?->nome_completo,
            'Concluido' => $task->status === 'concluido',
        ];
    }

    private function subtitulo(NursingTask $task): string
    {
        $partes = array_filter([$task->paciente?->nome_completo, $task->descricao]);

        return $partes ? implode(' • ', $partes) : ($task->leito?->codigo ?? '');
    }

    private function corBorda(NursingTask $task): string
    {
        return match (true) {
            $task->status === 'concluido' => 'var(--kordis-task-concluido)',
            $task->prioridade?->nome === Prioridade::URGENTE => 'var(--kordis-task-urgente)',
            $task->status === 'em_andamento' => 'var(--kordis-task-progresso)',
            default => 'var(--kordis-navy-800)',
        };
    }
}
