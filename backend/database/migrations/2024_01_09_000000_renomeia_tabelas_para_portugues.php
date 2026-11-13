<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tabelas = [
        'beds' => 'leitos',
        'patients' => 'pacientes',
        'clinical_records' => 'prontuarios',
        'nursing_tasks' => 'tarefas_enfermagem',
        'surgical_rooms' => 'salas_cirurgicas',
        'surgical_bookings' => 'agendamentos_cirurgicos',
    ];

    public function up(): void
    {
        foreach ($this->tabelas as $de => $para) {
            Schema::rename($de, $para);
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas as $de => $para) {
            Schema::rename($para, $de);
        }
    }
};
