<?php

namespace App\Http\Requests;

use App\Models\TipoProntuario;
use App\Support\SimNao;
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
            'tipo_prontuario_id' => ['required', 'integer', Rule::exists(TipoProntuario::class, 'id')->where('status', SimNao::SIM)],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_prontuario_id.required' => 'Selecione o tipo de registro.',
            'tipo_prontuario_id.exists' => 'Tipo de registro inválido ou inativo.',
            'titulo.required' => 'Informe um título para o registro.',
            'descricao.required' => 'Descreva a evolução clínica.',
        ];
    }
}
