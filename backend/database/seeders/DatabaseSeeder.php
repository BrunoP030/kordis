<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Funcionario;
use App\Models\User;
use App\Support\SimNao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $cargo = Cargo::firstOrCreate(
            ['nome' => 'Administrador'],
            ['descricao' => 'Administração do sistema', 'ativo' => SimNao::SIM]
        );

        $funcionario = Funcionario::firstOrCreate(
            ['email' => 'admin@kordis.com'],
            ['nome_completo' => 'Administrador do Sistema', 'cargo_id' => $cargo->cargo_id, 'ativo' => SimNao::SIM]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@kordis.com'],
            [
                'name' => 'Administrador do Sistema',
                'password' => Hash::make('senha-forte-123'),
                'funcionario_id' => $funcionario->id,
                'ativo' => SimNao::SIM,
            ]
        );
        $admin->assignRole('Administrador');

        // Pacientes primeiro: leitos e tarefas referenciam pacientes.
        $this->call(PatientSeeder::class);
        $this->call(BedSeeder::class);
        $this->call(NursingTaskSeeder::class);
    }
}
