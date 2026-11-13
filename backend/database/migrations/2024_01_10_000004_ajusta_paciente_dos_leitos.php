<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O leito deixa de guardar o nome do paciente em texto e passa a apontar para pacientes.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leitos', function (Blueprint $table) {
            $table->foreignId('paciente_id')->nullable()->after('status')->constrained('pacientes')->nullOnDelete();
        });

        // Liga ao cadastro quando o nome digitado bate exatamente com um paciente.
        DB::statement(<<<'SQL'
            UPDATE leitos l
               SET paciente_id = p.id
              FROM pacientes p
             WHERE l.paciente_nome IS NOT NULL
               AND lower(btrim(l.paciente_nome)) = lower(p.nome_completo)
               AND p.deleted_at IS NULL
        SQL);

        Schema::table('leitos', function (Blueprint $table) {
            $table->dropColumn('paciente_nome');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Migration irreversível: restaure o backup em storage/app/backups.');
    }
};
