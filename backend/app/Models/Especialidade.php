<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidade extends Model
{
    protected $table = 'especialidades';

    protected $fillable = ['nome', 'descricao'];

    public function salas()
    {
        return $this->hasMany(SurgicalRoom::class, 'especialidade_id');
    }
}
