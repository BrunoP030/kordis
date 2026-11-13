<?php

use App\Support\SimNao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria o cadastro de funcionários (pessoa) e liga cada usuário do sistema a um funcionário.
 * CPF e registro profissional saem de "users" e passam a viver em "funcionarios".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome_completo');
            $table->string('cpf', 14)->nullable()->unique();
            $table->string('rg', 20)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->enum('sexo', ['M', 'F', 'Outro'])->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();
            $table->string('cargo', 80)->nullable();
            $table->string('registro_profissional', 30)->nullable()->comment('CRM, COREN etc.');
            $table->date('data_admissao')->nullable();
            $table->smallInteger('ativo')->default(SimNao::SIM);
            $table->timestamps();
            $table->softDeletes();
            $table->index('nome_completo');
        });
        SimNao::aplicar('funcionarios', 'ativo', 'Funcionário ativo');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('funcionario_id')->nullable()->after('id')->constrained('funcionarios')->nullOnDelete();
        });

        // Cada usuário existente vira um funcionário, levando CPF e registro profissional.
        foreach (DB::table('users')->get() as $usuario) {
            $funcionarioId = DB::table('funcionarios')->insertGetId([
                'nome_completo' => $usuario->name,
                'cpf' => $usuario->cpf,
                'email' => $usuario->email,
                'registro_profissional' => $usuario->registro_profissional,
                'ativo' => $usuario->ativo ? SimNao::SIM : SimNao::NAO,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('users')->where('id', $usuario->id)->update(['funcionario_id' => $funcionarioId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'registro_profissional']);
        });

        SimNao::converter('users', 'ativo', 'ativo', SimNao::SIM, 'Usuário ativo');
    }

    public function down(): void
    {
        throw new RuntimeException('Migration irreversível: restaure o backup em storage/app/backups.');
    }
};
