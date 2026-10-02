<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgical_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });

        Schema::create('surgical_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgical_room_id')->constrained('surgical_rooms');
            $table->string('procedimento');
            $table->string('cirurgiao');
            // LGPD: apenas iniciais/nº de prontuário, nunca o nome completo (tela vista por vários perfis).
            $table->string('paciente', 30)->nullable();
            $table->enum('status', ['agendado', 'em_andamento', 'atrasado', 'limpeza'])->default('agendado');
            $table->dateTime('inicio_previsto');
            $table->dateTime('fim_previsto');
            $table->dateTime('inicio_real')->nullable();
            $table->dateTime('fim_real')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['surgical_room_id', 'inicio_previsto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgical_bookings');
        Schema::dropIfExists('surgical_rooms');
    }
};
