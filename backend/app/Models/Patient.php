<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    /** alergias: 0 = Não, 1 = Sim (detalhes em paciente_alergias) */
    protected $fillable = [
        'nome_completo', 'cpf', 'cartao_sus', 'data_nascimento', 'sexo',
        'telefone', 'email', 'endereco', 'tipo_sanguineo', 'alergias', 'status', 'criado_por',
    ];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date', 'alergias' => 'integer'];
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function clinicalRecords()
    {
        return $this->hasMany(ClinicalRecord::class, 'paciente_id');
    }

    public function pacienteAlergias()
    {
        return $this->hasMany(PacienteAlergia::class, 'paciente_id');
    }

    /** Nomes das alergias que não foram descartadas (ativas e em investigação). */
    public function nomesAlergiasVigentes(): array
    {
        return $this->pacienteAlergias
            ->where('status', '!=', 'descartada')
            ->map(fn (PacienteAlergia $pa) => $pa->alergia->nome . ($pa->status === 'em_investigacao' ? ' (em investigação)' : ''))
            ->values()
            ->all();
    }

    public function getIdadeAttribute(): int
    {
        return $this->data_nascimento->age;
    }
}
