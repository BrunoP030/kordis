<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurgicalRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required', 'string', 'max:50',
                Rule::unique('surgical_rooms', 'nome')->ignore($this->route('surgicalRoom')?->id),
            ],
            'especialidade' => ['nullable', 'string', 'max:80'],
            'ativa' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da sala.',
            'nome.unique' => 'Já existe uma sala com esse nome.',
        ];
    }
}
