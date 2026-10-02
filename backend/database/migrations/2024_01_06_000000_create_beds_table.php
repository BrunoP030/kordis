<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->enum('status', ['ocupado', 'limpeza', 'disponivel'])->default('disponivel');
            $table->string('paciente_nome')->nullable();
            $table->timestamp('entrada_em')->nullable();
            $table->date('previsao_alta')->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
