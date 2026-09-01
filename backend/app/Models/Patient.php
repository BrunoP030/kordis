<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome_completo', 'cpf', 'cartao_sus', 'data_nascimento', 'sexo',
        'telefone', 'email', 'endereco', 'tipo_sanguineo', 'alergias', 'status', 'criado_por',
    ];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date'];
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function getIdadeAttribute(): int
    {
        return $this->data_nascimento->age;
    }
}
