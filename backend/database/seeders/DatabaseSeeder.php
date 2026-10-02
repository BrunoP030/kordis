<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@kordis.com'],
            [
                'name' => 'Administrador do Sistema',
                'password' => Hash::make('senha-forte-123'),
                'ativo' => true,
            ]
        );

        $admin->assignRole('Administrador');

        $this->call(NursingTaskSeeder::class);
        $this->call(SurgicalSeeder::class);
        $this->call(BedSeeder::class);
    }
}
