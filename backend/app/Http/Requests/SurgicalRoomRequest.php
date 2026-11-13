<?php

namespace App\Http\Requests;

use App\Models\Especialidade;
use App\Models\SurgicalRoom;
use App\Support\SimNao;
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
                Rule::unique(SurgicalRoom::class, 'nome')->ignore($this->route('surgicalRoom')?->id),
            ],
            'especialidade_id' => ['nullable', 'integer', Rule::exists(Especialidade::class, 'id')],
            'status' => ['sometimes', 'integer', Rule::in(SimNao::VALORES)], // 0 = Não, 1 = Sim
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
