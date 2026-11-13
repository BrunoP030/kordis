<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProntuario extends Model
{
    protected $table = 'tipos_prontuario';

    protected $fillable = ['nome', 'descricao', 'status'];

    protected function casts(): array
    {
        return ['status' => 'integer'];
    }
}
