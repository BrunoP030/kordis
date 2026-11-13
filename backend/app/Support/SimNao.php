<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Padrão dos campos "0 ou 1" do banco (smallint): 0 = Não, 1 = Sim.
 */
final class SimNao
{
    public const NAO = 0;
    public const SIM = 1;

    public const VALORES = [self::NAO, self::SIM];

    public const COMENTARIO = '0 = Não, 1 = Sim';

    /** Limita a coluna a 0/1 e registra a convenção no comentário da coluna. */
    public static function aplicar(string $tabela, string $coluna, string $descricao): void
    {
        DB::statement("ALTER TABLE {$tabela} ADD CONSTRAINT {$tabela}_{$coluna}_sim_nao CHECK ({$coluna} IN (0, 1))");
        DB::statement("COMMENT ON COLUMN {$tabela}.{$coluna} IS '{$descricao} (" . self::COMENTARIO . ")'");
    }

    /**
     * Converte uma coluna existente (boolean ou texto) para smallint 0/1.
     *
     * @param string $expressaoSim expressão SQL que vale verdadeiro quando o valor antigo significa "Sim"
     */
    public static function converter(string $tabela, string $coluna, string $expressaoSim, int $padrao, string $descricao): void
    {
        DB::statement("ALTER TABLE {$tabela} ALTER COLUMN {$coluna} DROP DEFAULT");
        DB::statement("ALTER TABLE {$tabela} ALTER COLUMN {$coluna} TYPE smallint USING (CASE WHEN {$expressaoSim} THEN 1 ELSE 0 END)");
        DB::statement("ALTER TABLE {$tabela} ALTER COLUMN {$coluna} SET DEFAULT {$padrao}");
        DB::statement("ALTER TABLE {$tabela} ALTER COLUMN {$coluna} SET NOT NULL");
        self::aplicar($tabela, $coluna, $descricao);
    }

    public static function rotulo(?int $valor): string
    {
        return $valor === self::SIM ? 'Sim' : 'Não';
    }
}
