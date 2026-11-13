<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalRecord extends Model
{
    protected $table = 'prontuarios';

    protected $fillable = ['paciente_id', 'autor_id', 'tipo_prontuario_id', 'titulo', 'descricao'];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoProntuario::class, 'tipo_prontuario_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
