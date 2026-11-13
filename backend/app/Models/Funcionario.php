<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    use SoftDeletes;

    protected $table = 'funcionarios';

    protected $fillable = [
        'nome_completo', 'cpf', 'rg', 'data_nascimento', 'sexo', 'telefone', 'email',
        'endereco', 'cargo_id', 'registro_profissional', 'data_admissao', 'ativo',
    ];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date', 'data_admissao' => 'date', 'ativo' => 'integer'];
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id', 'cargo_id');
    }

    public function usuario()
    {
        return $this->hasOne(User::class, 'funcionario_id');
    }
}
