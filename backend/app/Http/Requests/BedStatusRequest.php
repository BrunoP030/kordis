<?php

namespace App\Http\Requests;

use App\Models\Bed;
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
            'paciente_nome' => ['required_if:status,ocupado', 'nullable', 'string', 'max:255'],
            'entrada_em' => ['required_if:status,ocupado', 'nullable', 'date'],
            'previsao_alta' => ['nullable', 'date', 'after_or_equal:entrada_em'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido.',
            'paciente_nome.required_if' => 'Informe o paciente para ocupar o leito.',
            'entrada_em.required_if' => 'Informe a data/hora de entrada.',
            'previsao_alta.after_or_equal' => 'A previsão de alta não pode ser antes da entrada.',
        ];
    }
}
