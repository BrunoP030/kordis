<?php

namespace App\Http\Requests;

use App\Models\Bed;
use App\Models\Categoria;
use App\Models\Patient;
use App\Models\Prioridade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NursingTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'prioridade_id' => ['required', 'integer', Rule::exists(Prioridade::class, 'id')],
            'categoria_id' => ['required', 'integer', Rule::exists(Categoria::class, 'id')],
            'paciente_id' => ['nullable', 'integer', Rule::exists(Patient::class, 'id')->whereNull('deleted_at')],
            'leito_id' => ['nullable', 'integer', Rule::exists(Bed::class, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o título da tarefa.',
            'prioridade_id.required' => 'Selecione a prioridade.',
            'prioridade_id.exists' => 'Prioridade inválida.',
            'categoria_id.required' => 'Selecione a categoria.',
            'categoria_id.exists' => 'Categoria inválida.',
            'paciente_id.exists' => 'Paciente não encontrado.',
            'leito_id.exists' => 'Leito não encontrado.',
        ];
    }
}
