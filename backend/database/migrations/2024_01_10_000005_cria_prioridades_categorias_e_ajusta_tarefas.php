<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tarefas de enfermagem passam a referenciar cadastros em vez de guardar texto:
 * leito, paciente, prioridade e categoria viram FKs; o responsável passa a ser um funcionário.
 */
return new class extends Migration
{
    /** valor antigo da coluna "prioridade" => nome no cadastro */
    private const PRIORIDADES = ['urgente' => 'Urgente', 'normal' => 'Normal', 'baixa' => 'Baixa'];

    private const CATEGORIAS = ['Geral', 'Assistência', 'Higienização', 'Administrativa'];

    public function up(): void
    {
        Schema::create('prioridades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 40)->unique();
            $table->timestamps();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 40)->unique();
            $table->timestamps();
        });

        $prioridades = [];
        foreach (self::PRIORIDADES as $antigo => $nome) {
            $prioridades[$antigo] = DB::table('prioridades')->insertGetId(['nome' => $nome, 'created_at' => now(), 'updated_at' => now()]);
        }

        // As categorias fixas do sistema + qualquer outra que já tenha sido gravada.
        $nomesCategorias = collect(self::CATEGORIAS)
            ->merge(DB::table('tarefas_enfermagem')->whereNotNull('categoria')->distinct()->pluck('categoria'))
            ->unique();
        $categorias = [];
        foreach ($nomesCategorias as $nome) {
            $categorias[$nome] = DB::table('categorias')->insertGetId(['nome' => $nome, 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::table('tarefas_enfermagem', function (Blueprint $table) {
            $table->foreignId('prioridade_id')->nullable()->after('status')->constrained('prioridades');
            $table->foreignId('categoria_id')->nullable()->after('prioridade_id')->constrained('categorias');
            $table->foreignId('paciente_id')->nullable()->after('categoria_id')->constrained('pacientes')->nullOnDelete();
            $table->foreignId('leito_id')->nullable()->after('paciente_id')->constrained('leitos')->nullOnDelete();
        });

        foreach ($prioridades as $antigo => $id) {
            DB::table('tarefas_enfermagem')->where('prioridade', $antigo)->update(['prioridade_id' => $id]);
        }
        DB::table('tarefas_enfermagem')->whereNull('prioridade_id')->update(['prioridade_id' => $prioridades['normal']]);

        foreach ($categorias as $nome => $id) {
            DB::table('tarefas_enfermagem')->where('categoria', $nome)->update(['categoria_id' => $id]);
        }
        DB::table('tarefas_enfermagem')->whereNull('categoria_id')->update(['categoria_id' => $categorias['Geral']]);

        // "leito" era texto livre: aceita tanto "Leito 201" quanto só "201".
        DB::statement(<<<'SQL'
            UPDATE tarefas_enfermagem t
               SET leito_id = l.id
              FROM leitos l
             WHERE t.leito IS NOT NULL
               AND (lower(btrim(t.leito)) = lower(l.codigo) OR lower('Leito ' || btrim(t.leito)) = lower(l.codigo))
        SQL);

        DB::statement(<<<'SQL'
            UPDATE tarefas_enfermagem t
               SET paciente_id = p.id
              FROM pacientes p
             WHERE t.paciente_nome IS NOT NULL
               AND lower(btrim(t.paciente_nome)) = lower(p.nome_completo)
               AND p.deleted_at IS NULL
        SQL);

        // Responsável: troca a FK de users para funcionarios, convertendo o usuário no seu funcionário.
        DB::statement('ALTER TABLE tarefas_enfermagem DROP CONSTRAINT IF EXISTS nursing_tasks_responsavel_id_foreign');
        DB::statement(<<<'SQL'
            UPDATE tarefas_enfermagem t
               SET responsavel_id = u.funcionario_id
              FROM users u
             WHERE t.responsavel_id = u.id
        SQL);
        DB::statement('UPDATE tarefas_enfermagem SET responsavel_id = NULL WHERE responsavel_id NOT IN (SELECT id FROM funcionarios)');

        Schema::table('tarefas_enfermagem', function (Blueprint $table) {
            $table->dropColumn(['prioridade', 'categoria', 'paciente_nome', 'leito']);
            $table->foreign('responsavel_id')->references('id')->on('funcionarios')->nullOnDelete();
            $table->index('categoria_id');
        });

        DB::statement('ALTER TABLE tarefas_enfermagem ALTER COLUMN prioridade_id SET NOT NULL');
        DB::statement('ALTER TABLE tarefas_enfermagem ALTER COLUMN categoria_id SET NOT NULL');
    }

    public function down(): void
    {
        throw new RuntimeException('Migration irreversível: restaure o backup em storage/app/backups.');
    }
};
