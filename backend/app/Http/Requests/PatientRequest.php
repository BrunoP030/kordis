<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Support\SimNao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pacienteId = $this->route('patient')?->id;

        return [
            'nome_completo' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:14', Rule::unique(Patient::class, 'cpf')->ignore($pacienteId)],
            'cartao_sus' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', Rule::in(['M', 'F', 'Outro'])],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'tipo_sanguineo' => ['nullable', 'string', 'max:5'],
            'alergias' => ['sometimes', 'integer', Rule::in(SimNao::VALORES)], // 0 = Não, 1 = Sim
            'status' => ['sometimes', Rule::in(['ativo', 'internado', 'alta', 'obito'])],
        ];
    }
}
