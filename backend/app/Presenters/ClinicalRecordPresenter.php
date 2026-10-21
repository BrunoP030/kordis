<?php

namespace App\Presenters;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use Illuminate\Support\Collection;

class ClinicalRecordPresenter
{
    public function paraPaciente(Patient $paciente): array
    {
        return [
            'Id' => $paciente->id,
            'Nome' => $paciente->nome_completo,
            'Idade' => $paciente->idade,
            'Sexo' => $paciente->sexo,
            'TipoSanguineo' => $paciente->tipo_sanguineo,
            'Alergias' => $paciente->alergias,
            'Status' => $paciente->status,
        ];
    }

    /** @param Collection<int, Patient> $pacientes */
    public function paraPacientes(Collection $pacientes): array
    {
        return $pacientes->map(fn (Patient $p) => $this->paraPaciente($p))->values()->all();
    }

    public function paraRegistro(ClinicalRecord $registro): array
    {
        return [
            'Id' => $registro->id,
            'Tipo' => $registro->tipo,
            'Titulo' => $registro->titulo,
            'Descricao' => $registro->descricao,
            'Autor' => $registro->autor?->name,
            'CriadoEm' => $registro->created_at->format('Y-m-d\TH:i:s'),
        ];
    }

    /** @param Collection<int, ClinicalRecord> $registros */
    public function paraHistorico(Collection $registros): array
    {
        return $registros->map(fn (ClinicalRecord $r) => $this->paraRegistro($r))->values()->all();
    }
}
