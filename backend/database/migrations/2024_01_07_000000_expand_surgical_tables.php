<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O enum virou CHECK no Postgres; o fluxo agora tem mais status (concluido, cancelado),
        // validados na aplicação.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE surgical_bookings DROP CONSTRAINT IF EXISTS surgical_bookings_status_check');
        }

        Schema::table('surgical_rooms', function (Blueprint $table) {
            $table->string('especialidade', 80)->nullable()->after('nome');
        });

        Schema::table('surgical_bookings', function (Blueprint $table) {
            $table->string('tipo', 20)->default('eletiva')->after('status');          // eletiva|urgencia|emergencia
            $table->string('porte', 20)->nullable()->after('tipo');                    // pequeno|medio|grande|especial
            $table->string('anestesia', 30)->nullable()->after('porte');               // geral|raquidiana|peridural|sedacao|local|bloqueio
            $table->string('anestesista')->nullable()->after('anestesia');
            $table->text('equipe_apoio')->nullable()->after('anestesista');            // auxiliares, instrumentador, enfermagem
            $table->text('equipamentos')->nullable()->after('equipe_apoio');           // OPME, materiais, equipamentos especiais
            $table->boolean('reserva_uti')->default(false)->after('equipamentos');
            $table->boolean('reserva_sangue')->default(false)->after('reserva_uti');
            $table->text('motivo_cancelamento')->nullable()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('surgical_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'tipo', 'porte', 'anestesia', 'anestesista', 'equipe_apoio',
                'equipamentos', 'reserva_uti', 'reserva_sangue', 'motivo_cancelamento',
            ]);
        });

        Schema::table('surgical_rooms', function (Blueprint $table) {
            $table->dropColumn('especialidade');
        });
    }
};
