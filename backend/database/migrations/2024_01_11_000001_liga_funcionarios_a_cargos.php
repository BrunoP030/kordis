<?php

use App\Support\SimNao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O cargo do funcionário deixa de ser texto livre e passa a referenciar cargos.cargo_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->foreignId('cargo_id')->nullable()->after('endereco')
                ->constrained('cargos', 'cargo_id')->nullOnDelete();
        });

        // Cada cargo digitado vira um registro em cargos (sem duplicar por maiúscula/espaço).
        $cargos = [];
        foreach (DB::table('funcionarios')->whereNotNull('cargo')->get() as $funcionario) {
            $nome = preg_replace('/\s+/u', ' ', trim($funcionario->cargo));
            if ($nome === '') {
                continue;
            }
            $chave = mb_strtolower($nome);
            $cargos[$chave] ??= DB::table('cargos')->whereRaw('lower(nome) = ?', [$chave])->value('cargo_id')
                ?? DB::table('cargos')->insertGetId([
                    'nome' => $nome, 'ativo' => SimNao::SIM, 'created_at' => now(), 'updated_at' => now(),
                ], 'cargo_id');

            DB::table('funcionarios')->where('id', $funcionario->id)->update(['cargo_id' => $cargos[$chave]]);
        }

        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropColumn('cargo');
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->string('cargo', 80)->nullable()->after('endereco');
        });

        DB::statement('UPDATE funcionarios f SET cargo = c.nome FROM cargos c WHERE c.cargo_id = f.cargo_id');

        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_id');
        });
    }
};
