<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alergia extends Model
{
    protected $table = 'alergias';

    protected $fillable = ['nome', 'descricao'];
}
