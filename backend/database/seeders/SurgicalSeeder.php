<?php

namespace Database\Seeders;

use App\Models\SurgicalBooking;
use App\Models\SurgicalRoom;
use App\Models\User;
use Illuminate\Database\Seeder;

class SurgicalSeeder extends Seeder
{
    public function run(): void
    {
        $salas = collect(['Sala 01', 'Sala 02', 'Sala 03', 'Sala 04'])
            ->map(fn (string $nome) => SurgicalRoom::updateOrCreate(['nome' => $nome], ['ativa' => true]))
            ->values();

        $criadoPor = User::where('email', 'admin@kordis.com')->value('id');
        $hoje = today();

        $reservas = [
            [0, 'Catarata', 'Dra. Helena', 'M.S.', 'em_andamento', 8, 10],
            [0, 'Limpeza pós-operatória', 'Equipe de higienização', null, 'limpeza', 10, 11],
            [1, 'Neurocirurgia', 'Dr. Ricardo', 'J.P.', 'agendado', 10, 14],
            [2, 'Transplante Renal', 'Dr. Mendes', 'A.C.', 'em_andamento', 9, 15],
            // Início já passou e continua "agendado": aparece como "atrasado".
            [3, 'Ortopedia', 'Dr. Paulo', 'R.T.', 'agendado', 8, 10],
        ];

        foreach ($reservas as [$sala, $procedimento, $cirurgiao, $paciente, $status, $h1, $h2]) {
            SurgicalBooking::updateOrCreate(
                [
                    'surgical_room_id' => $salas[$sala]->id,
                    'procedimento' => $procedimento,
                    'inicio_previsto' => $hoje->copy()->setHour($h1),
                ],
                [
                    'cirurgiao' => $cirurgiao,
                    'paciente' => $paciente,
                    'status' => $status,
                    'fim_previsto' => $hoje->copy()->setHour($h2),
                    'criado_por' => $criadoPor,
                ]
            );
        }
    }
}
