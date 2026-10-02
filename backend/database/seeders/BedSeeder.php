<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\User;
use Illuminate\Database\Seeder;

class BedSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@kordis.com')->value('id');

        $leitos = [
            ['Leito 201', 'ocupado', 'Maria Souza', now()->subDays(2), now()->addDays(3)->toDateString(), null],
            ['Leito 202', 'ocupado', 'João Pereira', now()->subDay(), now()->addDays(5)->toDateString(), null],
            ['Leito 203', 'disponivel', null, null, null, null],
            ['Leito 205', 'limpeza', null, null, null, $admin],
            ['Leito 301', 'ocupado', 'Ana Lima', now()->subHours(10), now()->addDay()->toDateString(), null],
            ['Leito 302', 'disponivel', null, null, null, null],
            ['Leito 303', 'limpeza', null, null, null, $admin],
        ];

        foreach ($leitos as [$codigo, $status, $paciente, $entrada, $alta, $responsavel]) {
            Bed::updateOrCreate(['codigo' => $codigo], [
                'status' => $status,
                'paciente_nome' => $paciente,
                'entrada_em' => $entrada,
                'previsao_alta' => $alta,
                'responsavel_id' => $responsavel,
            ]);
        }
    }
}
