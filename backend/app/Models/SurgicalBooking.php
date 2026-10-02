<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgicalBooking extends Model
{
    protected $fillable = [
        'surgical_room_id', 'procedimento', 'cirurgiao', 'paciente', 'status',
        'inicio_previsto', 'fim_previsto', 'inicio_real', 'fim_real', 'observacoes', 'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'inicio_previsto' => 'datetime',
            'fim_previsto' => 'datetime',
            'inicio_real' => 'datetime',
            'fim_real' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(SurgicalRoom::class, 'surgical_room_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    /** Status exibido: "agendado" cujo início já passou vira "atrasado" na leitura. */
    public function getStatusEfetivoAttribute(): string
    {
        if ($this->status === 'agendado' && $this->inicio_previsto->isPast()) {
            return 'atrasado';
        }

        return $this->status;
    }
}
