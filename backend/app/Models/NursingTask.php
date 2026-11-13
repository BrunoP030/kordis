<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingTask extends Model
{
    protected $table = 'tarefas_enfermagem';

    public const STATUS = ['a_fazer', 'em_andamento', 'concluido'];

    public const CATEGORIA_HIGIENIZACAO = Categoria::HIGIENIZACAO;

    protected $fillable = [
        'titulo', 'descricao', 'status', 'prioridade_id', 'categoria_id',
        'paciente_id', 'leito_id', 'responsavel_id', 'concluido_em',
    ];

    protected function casts(): array
    {
        return ['concluido_em' => 'datetime'];
    }

    public function prioridade()
    {
        return $this->belongsTo(Prioridade::class, 'prioridade_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    public function leito()
    {
        return $this->belongsTo(Bed::class, 'leito_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(Funcionario::class, 'responsavel_id');
    }

    public function scopeHigienizacao($query)
    {
        return $query->whereHas('categoria', fn ($q) => $q->where('nome', self::CATEGORIA_HIGIENIZACAO));
    }

    public function ehHigienizacao(): bool
    {
        return $this->categoria?->nome === self::CATEGORIA_HIGIENIZACAO;
    }
}
