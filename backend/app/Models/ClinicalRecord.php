<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalRecord extends Model
{
    public const TIPOS = ['evolucao', 'prescricao', 'exame', 'procedimento', 'alta'];

    protected $fillable = ['patient_id', 'autor_id', 'tipo', 'titulo', 'descricao'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
