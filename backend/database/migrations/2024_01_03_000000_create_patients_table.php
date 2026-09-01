<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nome_completo');
            $table->string('cpf', 14)->unique();
            $table->string('cartao_sus', 20)->nullable();
            $table->date('data_nascimento');
            $table->enum('sexo', ['M', 'F', 'Outro']);
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();
            $table->string('tipo_sanguineo', 5)->nullable();
            $table->text('alergias')->nullable();
            $table->enum('status', ['ativo', 'internado', 'alta', 'obito'])->default('ativo');
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('nome_completo');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
