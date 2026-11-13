<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $table = 'leitos';

    public const STATUS = ['ocupado', 'limpeza', 'disponivel'];

    protected $fillable = [
        'codigo', 'status', 'paciente_id', 'entrada_em', 'previsao_alta', 'responsavel_id',
    ];

    protected function casts(): array
    {
        return ['entrada_em' => 'datetime', 'previsao_alta' => 'date'];
    }

    public function paciente()
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
