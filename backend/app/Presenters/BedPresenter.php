<?php

namespace App\Presenters;

use App\Models\Bed;
use Illuminate\Support\Collection;

class BedPresenter
{
    private const ROTULOS = [
        'ocupado' => 'Ocupado',
        'limpeza' => 'Limpeza',
        'disponivel' => 'Disponível',
    ];

    /** @param Collection<int, Bed> $leitos Lista plana; o agrupamento é feito pelo Grid. */
    public function paraGrid(Collection $leitos): array
    {
        return $leitos->map(fn (Bed $l) => $this->paraLinhaGrid($l))->values()->all();
    }

    public function paraLinhaGrid(Bed $leito): array
    {
        return [
            'Id' => $leito->id,
            'Codigo' => $leito->codigo,
            'Status' => $leito->status,
            'StatusLabel' => self::ROTULOS[$leito->status],
            'Paciente' => $leito->paciente_nome,
            'Entrada' => $leito->entrada_em?->format('d/m/Y H:i'),
            'PrevisaoAlta' => $leito->previsao_alta?->format('d/m/Y'),
            'Responsavel' => $leito->status === 'limpeza' ? $leito->responsavel?->name : null,
        ];
    }
}
