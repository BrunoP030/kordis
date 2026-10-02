<?php

namespace App\Http\Requests;

use App\Services\SurgicalBookingService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SurgicalBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'surgical_room_id' => ['required', 'integer', 'exists:surgical_rooms,id'],
            'procedimento' => ['required', 'string', 'max:255'],
            'cirurgiao' => ['required', 'string', 'max:255'],
            'paciente' => ['required', 'string', 'max:30'],
            'inicio_previsto' => ['required', 'date'],
            'fim_previsto' => ['required', 'date', 'after:inicio_previsto'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $conflito = app(SurgicalBookingService::class)->temConflito(
                (int) $this->input('surgical_room_id'),
                Carbon::parse($this->input('inicio_previsto')),
                Carbon::parse($this->input('fim_previsto')),
            );

            if ($conflito) {
                $validator->errors()->add('inicio_previsto', 'Já existe uma reserva nesta sala neste horário.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'surgical_room_id.required' => 'Selecione a sala.',
            'surgical_room_id.exists' => 'Sala inválida.',
            'procedimento.required' => 'Informe o procedimento.',
            'cirurgiao.required' => 'Informe o cirurgião.',
            'paciente.required' => 'Informe as iniciais ou o nº do prontuário do paciente.',
            'paciente.max' => 'Use apenas iniciais ou nº do prontuário (máx. 30 caracteres).',
            'inicio_previsto.required' => 'Informe o horário de início.',
            'fim_previsto.required' => 'Informe o horário de fim.',
            'fim_previsto.after' => 'O fim deve ser depois do início.',
        ];
    }
}
