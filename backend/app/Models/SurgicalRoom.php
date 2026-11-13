<?php

namespace App\Models;

use App\Support\SimNao;
use Illuminate\Database\Eloquent\Model;

class SurgicalRoom extends Model
{
    protected $table = 'salas_cirurgicas';

    /** status: 0 = Não (inativa), 1 = Sim (ativa) */
    protected $fillable = ['nome', 'especialidade_id', 'status'];

    protected function casts(): array
    {
        return ['status' => 'integer'];
    }

    public function especialidade()
    {
        return $this->belongsTo(Especialidade::class, 'especialidade_id');
    }

    public function bookings()
    {
        return $this->hasMany(SurgicalBooking::class);
    }

    public function scopeAtivas($query)
    {
        return $query->where('status', SimNao::SIM);
    }
}
