<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nursing_tasks', function (Blueprint $table) {
            $table->string('categoria', 40)->default('Geral')->after('prioridade');
            $table->index('categoria');
        });

        // Tarefas de higienização já existentes passam para a categoria certa.
        DB::table('nursing_tasks')->where('titulo', 'like', 'Higieniza%')->update(['categoria' => 'Higienização']);

        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30)->default('evolucao'); // evolucao|prescricao|exame|procedimento|alta
            $table->string('titulo');
            $table->text('descricao');
            $table->timestamps();
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_records');

        Schema::table('nursing_tasks', function (Blueprint $table) {
            $table->dropIndex(['categoria']);
            $table->dropColumn('categoria');
        });
    }
};
