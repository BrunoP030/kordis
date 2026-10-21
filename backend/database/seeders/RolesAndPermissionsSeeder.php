<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Uma permissão "visualizar" por item de menu (ver frontend/src/config/navigation.js)
        // + permissões finas de ação dentro de cada módulo.
        $permissoes = [
            'dashboard.visualizar',
            'tarefas.criar',
            'centro-cirurgico.visualizar', 'centro-cirurgico.gerenciar',
            'leitos.visualizar', 'leitos.gerenciar',
            'prontuarios.visualizar', 'prontuarios.criar', 'prontuarios.editar',
            'pacientes.visualizar', 'pacientes.criar', 'pacientes.editar', 'pacientes.excluir',
            'usuarios.gerenciar',
        ];

        foreach ($permissoes as $nome) {
            Permission::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);
        }

        $perfis = [
            // Acesso total ao sistema (conta administrativa).
            'Administrador' => Permission::all()->pluck('name')->all(),

            // Operacional: leitos completo, tarefas do Kanban, leitura de agenda cirúrgica e prontuários.
            'enfermeiro' => [
                'dashboard.visualizar', 'tarefas.criar',
                'leitos.visualizar', 'leitos.gerenciar',
                'centro-cirurgico.visualizar',
                'prontuarios.visualizar',
            ],

            // Clínico: prontuários completos e centro cirúrgico completo.
            'medico' => [
                'dashboard.visualizar',
                'prontuarios.visualizar', 'prontuarios.criar', 'prontuarios.editar',
                'centro-cirurgico.visualizar', 'centro-cirurgico.gerenciar',
            ],

            // Estritamente restrito: só acompanha tarefas de higienização e vê os leitos.
            'limpeza' => ['dashboard.visualizar', 'leitos.visualizar'],
        ];

        foreach ($perfis as $nome => $permissoesDoPerfil) {
            Role::firstOrCreate(['name' => $nome, 'guard_name' => 'web'])->syncPermissions($permissoesDoPerfil);
        }
    }
}
