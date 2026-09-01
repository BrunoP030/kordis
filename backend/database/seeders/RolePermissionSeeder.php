<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Uma permissão "visualizar" por item de menu (ver README do front:
        // config/navigation.js) + permissões finas de ação dentro de cada módulo.
        $permissoes = [
            'dashboard.visualizar',
            'centro-cirurgico.visualizar', 'centro-cirurgico.gerenciar',
            'leitos.visualizar', 'leitos.gerenciar',
            'chat-interno.visualizar',
            'prontuarios.visualizar', 'prontuarios.editar',
            'pacientes.visualizar', 'pacientes.criar', 'pacientes.editar', 'pacientes.excluir',
            'usuarios.gerenciar',
        ];

        foreach ($permissoes as $nome) {
            Permission::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $medico = Role::firstOrCreate(['name' => 'Médico', 'guard_name' => 'web']);
        $medico->syncPermissions([
            'dashboard.visualizar', 'centro-cirurgico.visualizar', 'leitos.visualizar',
            'chat-interno.visualizar', 'prontuarios.visualizar', 'prontuarios.editar',
            'pacientes.visualizar', 'pacientes.editar',
        ]);

        $enfermeiro = Role::firstOrCreate(['name' => 'Enfermeiro', 'guard_name' => 'web']);
        $enfermeiro->syncPermissions([
            'dashboard.visualizar', 'leitos.visualizar', 'leitos.gerenciar',
            'chat-interno.visualizar', 'prontuarios.visualizar',
            'pacientes.visualizar', 'pacientes.editar',
        ]);

        $recepcao = Role::firstOrCreate(['name' => 'Recepção', 'guard_name' => 'web']);
        $recepcao->syncPermissions([
            'dashboard.visualizar', 'chat-interno.visualizar',
            'pacientes.visualizar', 'pacientes.criar', 'pacientes.editar',
        ]);
    }
}
