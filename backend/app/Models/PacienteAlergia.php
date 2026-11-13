<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacienteAlergia extends Model
{
    protected $table = 'paciente_alergias';

    public const GRAVIDADES = ['leve', 'moderada', 'grave'];
    public const STATUS = ['ativa', 'descartada', 'em_investigacao'];

    protected $fillable = [
        'paciente_id', 'alergia_id', 'gravidade', 'reacao', 'observacao', 'status', 'data_identificacao',
    ];

    protected function casts(): array
    {
        return ['data_identificacao' => 'date'];
    }

    public function paciente()
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    public function alergia()
    {
        return $this->belongsTo(Alergia::class, 'alergia_id');
    }
}
