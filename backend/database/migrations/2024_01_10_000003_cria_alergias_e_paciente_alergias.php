<?php

use App\Support\SimNao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alergias saem do texto livre do paciente para um cadastro (alergias) e um vínculo
 * paciente × alergia com gravidade, reação e situação. Em "pacientes", o campo
 * "alergias" passa a ser só o indicador 0/1 (tem ou não alergia registrada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('paciente_alergias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('alergia_id')->constrained('alergias');
            $table->enum('gravidade', ['leve', 'moderada', 'grave']);
            $table->text('reacao');
            $table->text('observacao')->nullable();
            $table->enum('status', ['ativa', 'descartada', 'em_investigacao'])->default('ativa');
            $table->date('data_identificacao');
            $table->timestamps();
            $table->unique(['paciente_id', 'alergia_id']);
        });

        // O texto antigo ("Dipirona, Penicilina") vira um vínculo por alergia. Como o texto não
        // dizia gravidade nem reação, o registro entra "em investigação" para a equipe revisar.
        $alergias = [];
        foreach (DB::table('pacientes')->whereNotNull('alergias')->get() as $paciente) {
            foreach (preg_split('/[,;\n]+/u', $paciente->alergias) as $nome) {
                $nome = preg_replace('/\s+/u', ' ', trim($nome));
                if ($nome === '') {
                    continue;
                }
                $chave = mb_strtolower($nome);
                $alergias[$chave] ??= DB::table('alergias')->whereRaw('lower(nome) = ?', [$chave])->value('id')
                    ?? DB::table('alergias')->insertGetId(['nome' => $nome, 'created_at' => now(), 'updated_at' => now()]);

                DB::table('paciente_alergias')->insertOrIgnore([
                    'paciente_id' => $paciente->id,
                    'alergia_id' => $alergias[$chave],
                    'gravidade' => 'moderada',
                    'reacao' => 'Não informada (migrado do cadastro anterior)',
                    'observacao' => 'Registro migrado do campo de texto "alergias"; revisar gravidade e reação.',
                    'status' => 'em_investigacao',
                    'data_identificacao' => substr((string) $paciente->created_at, 0, 10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        SimNao::converter('pacientes', 'alergias', "alergias IS NOT NULL AND btrim(alergias) <> ''", SimNao::NAO, 'Paciente possui alergia');
    }

    public function down(): void
    {
        throw new RuntimeException('Migration irreversível: restaure o backup em storage/app/backups.');
    }
};
