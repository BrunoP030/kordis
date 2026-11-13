<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Categoria;
use App\Models\NursingTask;
use App\Models\Prioridade;
use Illuminate\Database\Seeder;

class NursingTaskSeeder extends Seeder
{
    public function run(): void
    {
        $prioridade = fn (string $nome) => Prioridade::where('nome', $nome)->value('id');
        $categoria = fn (string $nome) => Categoria::idPorNome($nome);
        $leito = fn (string $codigo) => Bed::where('codigo', $codigo)->value('id');

        $tarefas = [
            ['titulo' => 'Emergência - Leito 201', 'descricao' => 'Atendimento imediato', 'status' => 'a_fazer', 'prioridade_id' => $prioridade('Urgente'), 'categoria_id' => $categoria('Geral'), 'leito_id' => $leito('Leito 201')],
            ['titulo' => 'Monitoramento IoT', 'descricao' => 'Sinais vitais em acompanhamento', 'status' => 'em_andamento', 'prioridade_id' => $prioridade('Normal'), 'categoria_id' => $categoria('Geral')],
            ['titulo' => 'Triagem de Admissão', 'descricao' => 'Novos pacientes', 'status' => 'a_fazer', 'prioridade_id' => $prioridade('Normal'), 'categoria_id' => $categoria('Geral')],
            ['titulo' => 'Higienização Leito 302', 'descricao' => 'Finalizada', 'status' => 'concluido', 'prioridade_id' => $prioridade('Baixa'), 'categoria_id' => $categoria(Categoria::HIGIENIZACAO), 'leito_id' => $leito('Leito 302'), 'concluido_em' => now()],
        ];

        foreach ($tarefas as $tarefa) {
            NursingTask::updateOrCreate(['titulo' => $tarefa['titulo']], $tarefa);
        }
    }
}
