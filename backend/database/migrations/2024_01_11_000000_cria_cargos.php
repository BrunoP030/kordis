<?php

use App\Support\SimNao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cadastro de cargos (função do funcionário).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id('cargo_id');
            $table->string('nome', 80);
            $table->string('descricao')->nullable();
            $table->smallInteger('ativo')->default(SimNao::SIM);
            $table->timestamps();
        });
        SimNao::aplicar('cargos', 'ativo', 'Cargo ativo');
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
