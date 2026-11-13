<?php

namespace App\Http\Requests;

use App\Models\Bed;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BedStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Bed::STATUS)],
            'paciente_id' => ['required_if:status,ocupado', 'nullable', 'integer', Rule::exists(Patient::class, 'id')->whereNull('deleted_at')],
            'entrada_em' => ['required_if:status,ocupado', 'nullable', 'date'],
            'previsao_alta' => ['nullable', 'date', 'after_or_equal:entrada_em'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'paciente_id.required_if' => 'Selecione o paciente para ocupar o leito.',
            'paciente_id.exists' => 'Paciente não encontrado.',
            'entrada_em.required_if' => 'Informe a data/hora de entrada.',
            'previsao_alta.after_or_equal' => 'A previsão de alta não pode ser antes da entrada.',
        ];
    }
}
