<?php

namespace App\Services;

use App\Cadastros\RegistroCadastros;
use App\Support\SimNao;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CRUD genérico dos cadastros definidos em RegistroCadastros.
 */
class CadastroService
{
    private const MENSAGENS = [
        'required' => 'Preencha o campo :attribute.',
        'max' => 'O campo :attribute aceita no máximo :max caracteres.',
        'unique' => 'Já existe um cadastro com esse valor em :attribute.',
        'exists' => 'Selecione uma opção válida (e ativa) em :attribute.',
        'in' => 'Valor inválido em :attribute.',
        'integer' => 'Valor inválido em :attribute.',
        'date' => 'Data inválida em :attribute.',
        'email' => 'Informe um e-mail válido.',
        'before' => 'A data em :attribute deve ser anterior a hoje.',
        'before_or_equal' => 'A data em :attribute não pode ser no futuro.',
    ];

    /** Definições públicas para a tela montar grid e formulário. */
    public function definicoes(): array
    {
        return collect(RegistroCadastros::todos())->map(fn (array $def, string $chave) => [
            'chave' => $chave,
            'titulo' => $def['titulo'],
            'singular' => $def['singular'],
            'protegidos' => $def['protegidos'] ?? [],
            'campos' => array_map(fn (array $c) => [
                'nome' => $c['nome'],
                'rotulo' => $c['rotulo'],
                'tipo' => $c['tipo'],
                'obrigatorio' => $c['obrigatorio'],
                'max' => $c['max'],
                'grid' => $c['grid'],
                'recurso' => $c['recurso'],
                'opcoes' => array_map(fn ($o) => ['valor' => $o[0], 'texto' => $o[1]], $c['opcoes']),
            ], $def['campos']),
        ])->values()->all();
    }

    public function listar(string $chave): array
    {
        $def = RegistroCadastros::obter($chave);
        $registros = $def['modelo']::query()->orderBy($this->ordem($def))->get();

        // Nome de exibição de cada FK, numa consulta por campo.
        $nomesFk = [];
        foreach ($this->camposFk($def) as $campo) {
            $ids = $registros->pluck($campo['nome'])->filter()->unique()->values();
            $nomesFk[$campo['nome']] = $ids->isEmpty() ? collect() : $this->nomesPorId($campo['recurso'], $ids->all());
        }

        return $registros->map(fn (Model $r) => $this->paraLinha($def, $r, $nomesFk))->all();
    }

    /** Opções de um cadastro para combobox de FK: só os ativos (+ o valor atual, se informado). */
    public function opcoes(string $chave, ?int $incluirId = null): array
    {
        $def = RegistroCadastros::obter($chave);
        $modelo = new $def['modelo'];
        $pk = $modelo->getKeyName();

        return $def['modelo']::query()
            ->when($def['ativo'], fn ($q) => $q->where(fn ($q) => $q
                ->where($def['ativo'], SimNao::SIM)
                ->when($incluirId, fn ($q) => $q->orWhere($pk, $incluirId))))
            ->orderBy($def['rotulo'])
            ->get([$pk, $def['rotulo']])
            ->map(fn (Model $m) => ['Id' => $m->getKey(), 'Nome' => $m->{$def['rotulo']}])
            ->all();
    }

    public function criar(string $chave, array $entrada): array
    {
        $def = RegistroCadastros::obter($chave);
        $dados = $this->validar($def, $entrada);

        $registro = DB::transaction(function () use ($def, $dados) {
            $registro = $def['modelo']::create($dados + (isset($def['aoCriar']) ? ($def['aoCriar'])() : []));
            $this->depoisDeSalvar($def, $registro);

            return $registro;
        });

        return $this->linhaDe($def, $registro->refresh());
    }

    public function atualizar(string $chave, int|string $id, array $entrada): array
    {
        $def = RegistroCadastros::obter($chave);
        $registro = $def['modelo']::query()->findOrFail($id);
        $dados = $this->validar($def, $entrada, $registro);

        if ($this->protegido($def, $registro) && isset($dados[$def['rotulo']]) && $dados[$def['rotulo']] !== $registro->{$def['rotulo']}) {
            throw ValidationException::withMessages([
                $def['rotulo'] => 'Este registro é usado pelo sistema e não pode ser renomeado.',
            ]);
        }

        DB::transaction(function () use ($def, $registro, $dados) {
            $registro->update($dados);
            $this->depoisDeSalvar($def, $registro);
        });

        return $this->linhaDe($def, $registro->refresh());
    }

    public function excluir(string $chave, int|string $id): void
    {
        $def = RegistroCadastros::obter($chave);
        $registro = $def['modelo']::query()->findOrFail($id);

        if ($this->protegido($def, $registro)) {
            throw ValidationException::withMessages(['geral' => 'Este registro é usado pelo sistema e não pode ser excluído.']);
        }

        if ($usadoEm = $this->usadoEm($registro)) {
            throw ValidationException::withMessages([
                'geral' => "Não é possível excluir: este registro está sendo usado em {$usadoEm}."
                    . ($def['ativo'] ? ' Se não for mais usado, marque-o como inativo.' : ''),
            ]);
        }

        try {
            $registro->delete();
        } catch (QueryException $e) {
            // 23503 = violação de chave estrangeira (registro em uso em outra tabela).
            if ($e->getCode() === '23503') {
                throw ValidationException::withMessages([
                    'geral' => 'Não é possível excluir: este registro está sendo usado em outro cadastro.',
                ]);
            }
            throw $e;
        }
    }

    // ---------------------------------------------------------------------

    private function validar(array $def, array $entrada, ?Model $registro = null): array
    {
        $regras = [];
        $atributos = [];

        foreach ($def['campos'] as $campo) {
            $nome = $campo['nome'];
            $atributos[$nome] = $campo['rotulo'];
            $r = [$campo['obrigatorio'] || $campo['tipo'] === 'simnao' ? 'required' : 'nullable'];

            switch ($campo['tipo']) {
                case 'texto':
                case 'textarea':
                    $r[] = 'string';
                    break;
                case 'email':
                    $r[] = 'email';
                    break;
                case 'data':
                    $r[] = 'date';
                    break;
                case 'simnao':
                    $r[] = 'integer';
                    $r[] = Rule::in(SimNao::VALORES);
                    break;
                case 'opcoes':
                    $r[] = Rule::in(array_column($campo['opcoes'], 0));
                    break;
                case 'fk':
                    $r[] = 'integer';
                    $r[] = $this->regraFk($campo, $entrada[$nome] ?? null, $registro?->{$nome});
                    break;
            }

            if ($campo['max']) {
                $r[] = 'max:' . $campo['max'];
            }
            if ($campo['unico']) {
                $r[] = Rule::unique($def['modelo'], $nome)->ignore($registro?->getKey(), $registro?->getKeyName() ?? 'id');
            }
            if ($campo['unicoCom']) {
                $r[] = Rule::unique($def['modelo'], $nome)
                    ->where($campo['unicoCom'], $entrada[$campo['unicoCom']] ?? null)
                    ->ignore($registro?->getKey(), $registro?->getKeyName() ?? 'id');
            }

            $regras[$nome] = array_merge($r, $campo['regras']);
        }

        $mensagens = self::MENSAGENS;
        if ($campoComposto = collect($def['campos'])->firstWhere('unicoCom')) {
            $mensagens[$campoComposto['nome'] . '.unique'] = 'Esta combinação já está cadastrada.';
        }

        $dados = Validator::make($entrada, $regras, $mensagens, $atributos)->validate();

        foreach ($def['campos'] as $campo) {
            if ($campo['tipo'] === 'simnao' && isset($dados[$campo['nome']])) {
                $dados[$campo['nome']] = (int) $dados[$campo['nome']];
            }
        }

        return $dados;
    }

    /** FK precisa apontar para um registro ativo — exceto se for o valor que o registro já tinha. */
    private function regraFk(array $campo, mixed $valorNovo, mixed $valorAtual)
    {
        $alvo = RegistroCadastros::obter($campo['recurso']);
        $modelo = new $alvo['modelo'];
        $regra = Rule::exists($modelo->getTable(), $modelo->getKeyName());

        if (in_array(SoftDeletes::class, class_uses_recursive($modelo), true)) {
            $regra->whereNull($modelo->getDeletedAtColumn());
        }
        $mantendoValor = $valorAtual !== null && (string) $valorNovo === (string) $valorAtual;
        if ($alvo['ativo'] && ! $mantendoValor) {
            $regra->where($alvo['ativo'], SimNao::SIM);
        }

        return $regra;
    }

    /**
     * Tabelas que apontam para este registro. Ignora FKs "em cascata" (registros filhos que
     * pertencem a ele) e linhas já excluídas (soft delete). Retorna null se não estiver em uso.
     */
    private function usadoEm(Model $registro): ?string
    {
        $referencias = DB::select(<<<'SQL'
            SELECT cl.relname AS tabela, a.attname AS coluna
              FROM pg_constraint con
              JOIN pg_class cl ON cl.oid = con.conrelid
              JOIN pg_attribute a ON a.attrelid = con.conrelid AND a.attnum = con.conkey[1]
             WHERE con.contype = 'f'
               AND con.confrelid = ?::regclass
               AND con.confdeltype <> 'c'
        SQL, [$registro->getTable()]);

        $usos = [];
        foreach ($referencias as $ref) {
            $query = DB::table($ref->tabela)->where($ref->coluna, $registro->getKey());
            if (Schema::hasColumn($ref->tabela, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if ($total = $query->count()) {
                $usos[] = ($this->nomesTabelas()[$ref->tabela] ?? $ref->tabela) . " ({$total})";
            }
        }

        return $usos ? implode(', ', $usos) : null;
    }

    /** tabela do banco => nome amigável para mensagens */
    private function nomesTabelas(): array
    {
        $nomes = [
            'users' => 'Usuários',
            'leitos' => 'Leitos',
            'tarefas_enfermagem' => 'Tarefas de enfermagem',
            'salas_cirurgicas' => 'Salas cirúrgicas',
            'agendamentos_cirurgicos' => 'Agenda cirúrgica',
            'prontuarios' => 'Prontuários',
        ];
        foreach (RegistroCadastros::todos() as $def) {
            $nomes[(new $def['modelo'])->getTable()] = $def['titulo'];
        }

        return $nomes;
    }

    private function nomesPorId(string $recurso, array $ids)
    {
        $alvo = RegistroCadastros::obter($recurso);
        $pk = (new $alvo['modelo'])->getKeyName();

        // withTrashed quando houver: um paciente excluído continua com nome no histórico.
        $query = $alvo['modelo']::query();
        if (method_exists($query->getModel(), 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->whereIn($pk, $ids)->pluck($alvo['rotulo'], $pk);
    }

    private function linhaDe(array $def, Model $registro): array
    {
        $nomesFk = [];
        foreach ($this->camposFk($def) as $campo) {
            $id = $registro->{$campo['nome']};
            $nomesFk[$campo['nome']] = $id ? $this->nomesPorId($campo['recurso'], [$id]) : collect();
        }

        return $this->paraLinha($def, $registro, $nomesFk);
    }

    private function paraLinha(array $def, Model $registro, array $nomesFk): array
    {
        $linha = ['Id' => $registro->getKey(), 'Protegido' => $this->protegido($def, $registro)];

        foreach ($def['campos'] as $campo) {
            $valor = $registro->{$campo['nome']};
            $linha[$campo['nome']] = $valor instanceof DateTimeInterface ? $valor->format('Y-m-d') : $valor;

            if ($campo['tipo'] === 'fk') {
                $linha[$campo['nome'] . '__nome'] = $valor ? ($nomesFk[$campo['nome']][$valor] ?? null) : null;
            }
        }

        return $linha;
    }

    private function camposFk(array $def): array
    {
        return array_filter($def['campos'], fn ($c) => $c['tipo'] === 'fk');
    }

    private function ordem(array $def): string
    {
        return $def['ordem'] ?? $def['rotulo'] ?? (new $def['modelo'])->getKeyName();
    }

    private function protegido(array $def, Model $registro): bool
    {
        return $def['rotulo'] && in_array($registro->{$def['rotulo']}, $def['protegidos'], true);
    }

    private function depoisDeSalvar(array $def, Model $registro): void
    {
        if (isset($def['depoisDeSalvar'])) {
            ($def['depoisDeSalvar'])($registro);
        }
    }
}
