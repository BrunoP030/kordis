<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingTask extends Model
{
    public const STATUS = ['a_fazer', 'em_andamento', 'concluido'];
    public const CATEGORIA_HIGIENIZACAO = 'Higienização';
    public const CATEGORIAS = ['Geral', 'Assistência', 'Higienização', 'Administrativa'];

    protected $fillable = [
        'titulo', 'descricao', 'status', 'prioridade', 'categoria',
        'paciente_nome', 'leito', 'responsavel_id', 'concluido_em',
    ];

    protected function casts(): array
    {
        return ['concluido_em' => 'datetime'];
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
