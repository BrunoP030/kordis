<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prioridade extends Model
{
    protected $table = 'prioridades';

    public const URGENTE = 'Urgente';

    protected $fillable = ['nome'];
}
