<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('status', ['a_fazer', 'em_andamento', 'concluido'])->default('a_fazer');
            $table->enum('prioridade', ['urgente', 'normal', 'baixa'])->default('normal');
            $table->string('paciente_nome')->nullable();
            $table->string('leito', 20)->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_tasks');
    }
};
