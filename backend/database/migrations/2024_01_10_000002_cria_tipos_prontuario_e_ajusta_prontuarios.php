<?php

use App\Support\SimNao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O tipo do registro clínico passa a ser cadastro (tipos_prontuario) e a coluna do paciente
 * vira paciente_id (a FK para pacientes é mantida pelo rename).
 */
return new class extends Migration
{
    /** valor antigo da coluna "tipo" => [nome, descrição] */
    private const TIPOS = [
        'evolucao' => ['Evolução', 'Evolução clínica do paciente'],
        'prescricao' => ['Prescrição', 'Prescrição de medicamentos e cuidados'],
        'exame' => ['Exame', 'Resultado ou solicitação de exame'],
        'procedimento' => ['Procedimento', 'Procedimento realizado no paciente'],
        'alta' => ['Alta', 'Registro de alta hospitalar'],
    ];

    public function up(): void
    {
        Schema::create('tipos_prontuario', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60);
            $table->string('descricao')->nullable();
            $table->smallInteger('status')->default(SimNao::SIM);
            $table->timestamps();
        });
        SimNao::aplicar('tipos_prontuario', 'status', 'Tipo disponível para novos registros');

        $ids = [];
        foreach (self::TIPOS as $antigo => [$nome, $descricao]) {
            $ids[$antigo] = DB::table('tipos_prontuario')->insertGetId([
                'nome' => $nome, 'descricao' => $descricao, 'status' => SimNao::SIM,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('prontuarios', function (Blueprint $table) {
            $table->renameColumn('patient_id', 'paciente_id');
            $table->foreignId('tipo_prontuario_id')->nullable()->after('autor_id')->constrained('tipos_prontuario');
        });

        foreach ($ids as $antigo => $id) {
            DB::table('prontuarios')->where('tipo', $antigo)->update(['tipo_prontuario_id' => $id]);
        }
        // Algum valor fora da lista antiga cai em "Evolução" (era o padrão da coluna).
        DB::table('prontuarios')->whereNull('tipo_prontuario_id')->update(['tipo_prontuario_id' => $ids['evolucao']]);

        Schema::table('prontuarios', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
        DB::statement('ALTER TABLE prontuarios ALTER COLUMN tipo_prontuario_id SET NOT NULL');
    }

    public function down(): void
    {
        throw new RuntimeException('Migration irreversível: restaure o backup em storage/app/backups.');
    }
};
