<?php

namespace App\Http\Requests;

use App\Models\ClinicalRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClinicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(ClinicalRecord::TIPOS)],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecione o tipo de registro.',
            'tipo.in' => 'Tipo de registro inválido.',
            'titulo.required' => 'Informe um título para o registro.',
            'descricao.required' => 'Descreva a evolução clínica.',
        ];
    }
}
