<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /** ativo: 0 = Não, 1 = Sim. Dados da pessoa (CPF, registro profissional) ficam em funcionarios. */
    protected $fillable = ['name', 'email', 'funcionario_id', 'password', 'ativo'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'integer',
        ];
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    /** Perfil de limpeza e nenhum outro: enxerga apenas as tarefas de higienização. */
    public function apenasLimpeza(): bool
    {
        $perfis = $this->getRoleNames();

        return $perfis->count() === 1 && $perfis->first() === 'limpeza';
    }

    public function permissoesParaFrontend(): array
    {
        return [
            'perfis' => $this->getRoleNames(),
            'permissoes' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
