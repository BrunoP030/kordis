<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    public const HIGIENIZACAO = 'Higienização';

    protected $fillable = ['nome'];

    public static function idPorNome(string $nome): ?int
    {
        return static::where('nome', $nome)->value('id');
    }
}
