<?php

namespace Database\Seeders;

use App\Models\NursingTask;
use Illuminate\Database\Seeder;

class NursingTaskSeeder extends Seeder
{
    public function run(): void
    {
        $tarefas = [
            ['titulo' => 'Emergência - Leito 201', 'descricao' => 'Atendimento imediato', 'status' => 'a_fazer', 'prioridade' => 'urgente', 'leito' => '201'],
            ['titulo' => 'Monitoramento IoT', 'descricao' => 'Sinais vitais em acompanhamento', 'status' => 'em_andamento', 'prioridade' => 'normal'],
            ['titulo' => 'Triagem de Admissão', 'descricao' => 'Novos pacientes', 'status' => 'a_fazer', 'prioridade' => 'normal'],
            ['titulo' => 'Higienização Leito 302', 'descricao' => 'Finalizada', 'status' => 'concluido', 'prioridade' => 'baixa', 'leito' => '302', 'concluido_em' => now()],
        ];

        foreach ($tarefas as $tarefa) {
            NursingTask::updateOrCreate(['titulo' => $tarefa['titulo']], $tarefa);
        }
    }
}
