<?php

namespace App\Http\Requests;

use App\Models\NursingTask;
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
            'prioridade' => ['required', Rule::in(['urgente', 'normal', 'baixa'])],
            'categoria' => ['required', Rule::in(NursingTask::CATEGORIAS)],
            'paciente_nome' => ['nullable', 'string', 'max:255'],
            'leito' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o título da tarefa.',
            'prioridade.required' => 'Selecione a prioridade.',
            'prioridade.in' => 'Prioridade inválida.',
            'categoria.required' => 'Selecione a categoria.',
            'categoria.in' => 'Categoria inválida.',
        ];
    }
}
