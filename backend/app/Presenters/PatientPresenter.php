<?php

namespace App\Presenters;

use App\Models\PacienteAlergia;
use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientPresenter
{
    public function paraGrid(LengthAwarePaginator $paginator): array
    {
        return [
            'result' => $paginator->getCollection()->map(fn (Patient $p) => $this->paraLinhaGrid($p))->all(),
            'count' => $paginator->total(),
        ];
    }

    public function paraLinhaGrid(Patient $paciente): array
    {
        return [
            'id' => $paciente->id,
            'nomeCompleto' => $paciente->nome_completo,
            'cpf' => $paciente->cpf,
            'dataNascimento' => $paciente->data_nascimento->format('Y-m-d'),
            'idade' => $paciente->idade,
            'sexo' => $paciente->sexo,
            'telefone' => $paciente->telefone,
            'status' => $paciente->status,
            'tipoSanguineo' => $paciente->tipo_sanguineo,
        ];
    }

    public function paraDetalhe(Patient $paciente): array
    {
        return [
            'id' => $paciente->id,
            'nomeCompleto' => $paciente->nome_completo,
            'cpf' => $paciente->cpf,
            'cartaoSus' => $paciente->cartao_sus,
            'dataNascimento' => $paciente->data_nascimento->format('Y-m-d'),
            'sexo' => $paciente->sexo,
            'telefone' => $paciente->telefone,
            'email' => $paciente->email,
            'endereco' => $paciente->endereco,
            'tipoSanguineo' => $paciente->tipo_sanguineo,
            'alergias' => $paciente->alergias,
            'listaAlergias' => $paciente->pacienteAlergias->map(fn (PacienteAlergia $pa) => [
                'id' => $pa->id,
                'alergiaId' => $pa->alergia_id,
                'alergia' => $pa->alergia->nome,
                'gravidade' => $pa->gravidade,
                'reacao' => $pa->reacao,
                'observacao' => $pa->observacao,
                'status' => $pa->status,
                'dataIdentificacao' => $pa->data_identificacao->format('Y-m-d'),
            ])->values()->all(),
            'status' => $paciente->status,
            'criadoPor' => $paciente->criadoPor?->name,
            'criadoEm' => $paciente->created_at->format('d/m/Y H:i'),
        ];
    }
}
