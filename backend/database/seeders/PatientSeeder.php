<?php

namespace Database\Seeders;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $autor = User::where('email', 'admin@kordis.com')->first();

        $pacientes = [
            [
                'nome_completo' => 'Maria das Graças Oliveira',
                'cpf' => '111.444.777-35',
                'cartao_sus' => '700000000000001',
                'data_nascimento' => '1958-03-14',
                'sexo' => 'F',
                'telefone' => '(11) 98888-0001',
                'tipo_sanguineo' => 'A+',
                'alergias' => 'Dipirona, Penicilina',
                'status' => 'internado',
                'registro' => ['evolucao', 'Evolução pós-operatória — D1', 'Paciente estável, afebril, sem queixas álgicas relevantes. Ferida operatória limpa e seca.'],
            ],
            [
                'nome_completo' => 'João Carlos Ferreira',
                'cpf' => '222.555.888-46',
                'cartao_sus' => '700000000000002',
                'data_nascimento' => '1972-11-02',
                'sexo' => 'M',
                'telefone' => '(11) 98888-0002',
                'tipo_sanguineo' => 'O+',
                'alergias' => null,
                'status' => 'internado',
                'registro' => ['prescricao', 'Prescrição de rotina', 'Ceftriaxona 1 g EV de 12/12h. Dipirona 1 g VO se dor ou febre. Manter hidratação venosa.'],
            ],
            [
                'nome_completo' => 'Ana Beatriz Souza',
                'cpf' => '333.666.999-57',
                'cartao_sus' => '700000000000003',
                'data_nascimento' => '1990-07-25',
                'sexo' => 'F',
                'telefone' => '(11) 98888-0003',
                'tipo_sanguineo' => 'B-',
                'alergias' => 'Látex',
                'status' => 'ativo',
                'registro' => ['exame', 'Hemograma completo', 'Sem alterações significativas. Hemoglobina 13,2 g/dL, leucócitos 6.800/mm³.'],
            ],
            [
                'nome_completo' => 'Carlos Eduardo Lima',
                'cpf' => '444.777.000-68',
                'cartao_sus' => '700000000000004',
                'data_nascimento' => '1965-01-30',
                'sexo' => 'M',
                'telefone' => '(11) 98888-0004',
                'tipo_sanguineo' => 'AB+',
                'alergias' => 'Contraste iodado',
                'status' => 'internado',
                'registro' => ['procedimento', 'Colecistectomia videolaparoscópica', 'Procedimento realizado sem intercorrências. Tempo cirúrgico de 55 minutos. Encaminhado à recuperação.'],
            ],
            [
                'nome_completo' => 'Fernanda Ribeiro Costa',
                'cpf' => '555.888.111-79',
                'cartao_sus' => '700000000000005',
                'data_nascimento' => '2001-09-09',
                'sexo' => 'F',
                'telefone' => '(11) 98888-0005',
                'tipo_sanguineo' => 'O-',
                'alergias' => null,
                'status' => 'alta',
                'registro' => ['alta', 'Alta hospitalar', 'Paciente em bom estado geral, orientada quanto a cuidados domiciliares e retorno ambulatorial em 7 dias.'],
            ],
        ];

        foreach ($pacientes as $dados) {
            [$tipo, $titulo, $descricao] = $dados['registro'];
            unset($dados['registro']);

            $paciente = Patient::updateOrCreate(
                ['cpf' => $dados['cpf']],
                $dados + ['criado_por' => $autor?->id]
            );

            ClinicalRecord::firstOrCreate(
                ['patient_id' => $paciente->id, 'titulo' => $titulo],
                ['autor_id' => $autor?->id, 'tipo' => $tipo, 'descricao' => $descricao]
            );
        }
    }
}
