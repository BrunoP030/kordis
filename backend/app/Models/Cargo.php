<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table = 'cargos';

    protected $primaryKey = 'cargo_id';

    /** ativo: 0 = Não, 1 = Sim */
    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'integer'];
    }

    public function funcionarios()
    {
        return $this->hasMany(Funcionario::class, 'cargo_id', 'cargo_id');
    }
}
