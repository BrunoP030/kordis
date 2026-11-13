<?php

namespace App\Cadastros;

use App\Models\Alergia;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Especialidade;
use App\Models\Funcionario;
use App\Models\PacienteAlergia;
use App\Models\Patient;
use App\Models\Prioridade;
use App\Models\TipoProntuario;
use Illuminate\Support\Facades\Auth;

/**
 * Definição dos cadastros simples da tela "Cadastro" (menu lateral).
 *
 * Cada cadastro diz qual model usa, qual coluna é o rótulo (texto mostrado nos combobox),
 * qual coluna indica "ativo" (0 = Não, 1 = Sim — só os ativos aparecem como opção de FK)
 * e a lista de campos. Tipos de campo:
 *   texto | textarea | email | data | simnao (0/1) | opcoes (lista fixa) | fk (outro cadastro)
 *
 * Para incluir um cadastro novo, basta acrescentar uma entrada aqui.
 */
final class RegistroCadastros
{
    private const SEXOS = [['M', 'Masculino'], ['F', 'Feminino'], ['Outro', 'Outro']];

    private static ?array $cache = null;

    /** @return array<string, array> */
    public static function todos(): array
    {
        return self::$cache ??= [
            'cargos' => [
                'titulo' => 'Cargos',
                'singular' => 'cargo',
                'modelo' => Cargo::class,
                'rotulo' => 'nome',
                'ativo' => 'ativo',
                'campos' => [
                    self::campo('nome', 'Nome', 'texto', obrigatorio: true, max: 80, unico: true),
                    self::campo('descricao', 'Descrição', 'texto', max: 255),
                    self::campo('ativo', 'Ativo', 'simnao'),
                ],
            ],
            'funcionarios' => [
                'titulo' => 'Funcionários',
                'singular' => 'funcionário',
                'modelo' => Funcionario::class,
                'rotulo' => 'nome_completo',
                'ativo' => 'ativo',
                'campos' => [
                    self::campo('nome_completo', 'Nome completo', 'texto', obrigatorio: true, max: 255),
                    self::campo('cpf', 'CPF', 'texto', max: 14, unico: true),
                    self::campo('rg', 'RG', 'texto', max: 20, grid: false),
                    self::campo('data_nascimento', 'Data de nascimento', 'data', grid: false, regras: ['before:today']),
                    self::campo('sexo', 'Sexo', 'opcoes', opcoes: self::SEXOS, grid: false),
                    self::campo('telefone', 'Telefone', 'texto', max: 20),
                    self::campo('email', 'E-mail', 'email', max: 255, grid: false),
                    self::campo('endereco', 'Endereço', 'texto', max: 255, grid: false),
                    self::campo('cargo_id', 'Cargo', 'fk', recurso: 'cargos'),
                    self::campo('registro_profissional', 'Registro profissional (CRM, COREN…)', 'texto', max: 30, grid: false),
                    self::campo('data_admissao', 'Data de admissão', 'data', grid: false),
                    self::campo('ativo', 'Ativo', 'simnao'),
                ],
            ],
            'especialidades' => [
                'titulo' => 'Especialidades',
                'singular' => 'especialidade',
                'modelo' => Especialidade::class,
                'rotulo' => 'nome',
                'campos' => [
                    self::campo('nome', 'Nome', 'texto', obrigatorio: true, max: 80, unico: true),
                    self::campo('descricao', 'Descrição', 'texto', max: 255),
                ],
            ],
            'prioridades' => [
                'titulo' => 'Prioridades',
                'singular' => 'prioridade',
                'modelo' => Prioridade::class,
                'rotulo' => 'nome',
                'ordem' => 'id',
                // Usadas pelo sistema (cor do card urgente e tarefa automática de higienização).
                'protegidos' => [Prioridade::URGENTE, 'Normal'],
                'campos' => [
                    self::campo('nome', 'Nome', 'texto', obrigatorio: true, max: 40, unico: true),
                ],
            ],
            'categorias' => [
                'titulo' => 'Categorias de tarefa',
                'singular' => 'categoria',
                'modelo' => Categoria::class,
                'rotulo' => 'nome',
                'ordem' => 'id',
                // A equipe de limpeza enxerga as tarefas por esta categoria.
                'protegidos' => [Categoria::HIGIENIZACAO],
                'campos' => [
                    self::campo('nome', 'Nome', 'texto', obrigatorio: true, max: 40, unico: true),
                ],
            ],
            'tipos-prontuario' => [
                'titulo' => 'Tipos de prontuário',
                'singular' => 'tipo de prontuário',
                'modelo' => TipoProntuario::class,
                'rotulo' => 'nome',
                'ativo' => 'status',
                'ordem' => 'id',
                'campos' => [
                    self::campo('nome', 'Nome', 'texto', obrigatorio: true, max: 60, unico: true),
                    self::campo('descricao', 'Descrição', 'texto', max: 255),
                    self::campo('status', 'Ativo', 'simnao'),
                ],
            ],
            'alergias' => [
                'titulo' => 'Alergias',
                'singular' => 'alergia',
                'modelo' => Alergia::class,
                'rotulo' => 'nome',
                'campos' => [
                    self::campo('nome', 'Nome', 'texto', obrigatorio: true, max: 120, unico: true),
                    self::campo('descricao', 'Descrição', 'texto', max: 255),
                ],
            ],
            'pacientes' => [
                'titulo' => 'Pacientes',
                'singular' => 'paciente',
                'modelo' => Patient::class,
                'rotulo' => 'nome_completo',
                'aoCriar' => fn () => ['criado_por' => Auth::id()],
                'campos' => [
                    self::campo('nome_completo', 'Nome completo', 'texto', obrigatorio: true, max: 255),
                    self::campo('cpf', 'CPF', 'texto', obrigatorio: true, max: 14, unico: true),
                    self::campo('cartao_sus', 'Cartão SUS', 'texto', max: 20, grid: false),
                    self::campo('data_nascimento', 'Data de nascimento', 'data', obrigatorio: true, regras: ['before:today']),
                    self::campo('sexo', 'Sexo', 'opcoes', obrigatorio: true, opcoes: self::SEXOS),
                    self::campo('telefone', 'Telefone', 'texto', max: 20, grid: false),
                    self::campo('email', 'E-mail', 'email', max: 255, grid: false),
                    self::campo('endereco', 'Endereço', 'texto', max: 255, grid: false),
                    self::campo('tipo_sanguineo', 'Tipo sanguíneo', 'opcoes', opcoes: array_map(
                        fn ($t) => [$t, $t], ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
                    )),
                    self::campo('alergias', 'Possui alergia', 'simnao'),
                    self::campo('status', 'Situação', 'opcoes', obrigatorio: true, opcoes: [
                        ['ativo', 'Ativo'], ['internado', 'Internado'], ['alta', 'Alta'], ['obito', 'Óbito'],
                    ]),
                ],
            ],
            'paciente-alergias' => [
                'titulo' => 'Alergias dos pacientes',
                'singular' => 'alergia do paciente',
                'modelo' => PacienteAlergia::class,
                'ordem' => 'id',
                // Alergia vigente registrada => marca o paciente como "possui alergia" (1 = Sim).
                'depoisDeSalvar' => function (PacienteAlergia $registro) {
                    if ($registro->status !== 'descartada') {
                        Patient::whereKey($registro->paciente_id)->update(['alergias' => 1]);
                    }
                },
                'campos' => [
                    self::campo('paciente_id', 'Paciente', 'fk', obrigatorio: true, recurso: 'pacientes'),
                    self::campo('alergia_id', 'Alergia', 'fk', obrigatorio: true, recurso: 'alergias', unicoCom: 'paciente_id'),
                    self::campo('gravidade', 'Gravidade', 'opcoes', obrigatorio: true, opcoes: [
                        ['leve', 'Leve'], ['moderada', 'Moderada'], ['grave', 'Grave'],
                    ]),
                    self::campo('reacao', 'Reação', 'textarea', obrigatorio: true, max: 2000),
                    self::campo('observacao', 'Observação', 'textarea', max: 2000, grid: false),
                    self::campo('status', 'Situação', 'opcoes', obrigatorio: true, opcoes: [
                        ['ativa', 'Ativa'], ['descartada', 'Descartada'], ['em_investigacao', 'Em investigação'],
                    ]),
                    self::campo('data_identificacao', 'Data de identificação', 'data', obrigatorio: true, regras: ['before_or_equal:today']),
                ],
            ],
        ];
    }

    public static function existe(string $chave): bool
    {
        return array_key_exists($chave, self::todos());
    }

    public static function obter(string $chave): array
    {
        abort_unless(self::existe($chave), 404, 'Cadastro não encontrado.');

        return ['chave' => $chave] + self::todos()[$chave] + ['ativo' => null, 'protegidos' => [], 'rotulo' => null];
    }

    private static function campo(
        string $nome,
        string $rotulo,
        string $tipo,
        bool $obrigatorio = false,
        ?int $max = null,
        bool $unico = false,
        bool $grid = true,
        ?string $recurso = null,
        array $opcoes = [],
        array $regras = [],
        ?string $unicoCom = null,
    ): array {
        return compact('nome', 'rotulo', 'tipo', 'obrigatorio', 'max', 'unico', 'grid', 'recurso', 'opcoes', 'regras', 'unicoCom');
    }
}
