<?php

use App\Support\SimNao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Especialidade da sala vira cadastro próprio (especialidades) e "ativa" vira status 0/1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80)->unique();
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        Schema::table('salas_cirurgicas', function (Blueprint $table) {
            $table->foreignId('especialidade_id')->nullable()->after('nome')->constrained('especialidades')->nullOnDelete();
        });

        // Popula com as especialidades já digitadas nas salas, sem duplicar por maiúscula/espaço.
        $especialidades = [];
        foreach (DB::table('salas_cirurgicas')->whereNotNull('especialidade')->get() as $sala) {
            $nome = preg_replace('/\s+/u', ' ', trim($sala->especialidade));
            if ($nome === '') {
                continue;
            }
            $chave = mb_strtolower($nome);
            $especialidades[$chave] ??= DB::table('especialidades')->insertGetId([
                'nome' => mb_strtoupper(mb_substr($nome, 0, 1)) . mb_substr($nome, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('salas_cirurgicas')->where('id', $sala->id)->update(['especialidade_id' => $especialidades[$chave]]);
        }

        Schema::table('salas_cirurgicas', function (Blueprint $table) {
            $table->dropColumn('especialidade');
            $table->renameColumn('ativa', 'status');
        });

        SimNao::converter('salas_cirurgicas', 'status', 'status', SimNao::SIM, 'Sala ativa');
    }

    public function down(): void
    {
        throw new RuntimeException('Migration irreversível: restaure o backup em storage/app/backups.');
    }
};
