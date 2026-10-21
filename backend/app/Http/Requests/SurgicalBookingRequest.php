<?php

namespace App\Http\Requests;

use App\Models\SurgicalBooking;
use App\Services\SurgicalBookingService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurgicalBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'surgical_room_id' => ['required', 'integer', Rule::exists('surgical_rooms', 'id')->where('ativa', true)],
            'procedimento' => ['required', 'string', 'max:255'],
            'cirurgiao' => ['required', 'string', 'max:255'],
            'paciente' => ['required', 'string', 'max:30'],
            'inicio_previsto' => ['required', 'date'],
            'fim_previsto' => ['required', 'date', 'after:inicio_previsto'],
            'tipo' => ['required', Rule::in(SurgicalBooking::TIPOS)],
            'porte' => ['nullable', Rule::in(SurgicalBooking::PORTES)],
            'anestesia' => ['nullable', Rule::in(SurgicalBooking::ANESTESIAS)],
            'anestesista' => ['nullable', 'string', 'max:255'],
            'equipe_apoio' => ['nullable', 'string', 'max:1000'],
            'equipamentos' => ['nullable', 'string', 'max:1000'],
            'reserva_uti' => ['boolean'],
            'reserva_sangue' => ['boolean'],
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
                $this->route('surgicalBooking')?->id,
            );

            if ($conflito) {
                $validator->errors()->add('inicio_previsto', 'Já existe uma cirurgia nesta sala neste horário.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'surgical_room_id.required' => 'Selecione a sala.',
            'surgical_room_id.exists' => 'Sala inválida ou inativa.',
            'procedimento.required' => 'Informe o procedimento.',
            'cirurgiao.required' => 'Informe o cirurgião.',
            'paciente.required' => 'Informe as iniciais ou o nº do prontuário do paciente.',
            'paciente.max' => 'Use apenas iniciais ou nº do prontuário (máx. 30 caracteres).',
            'inicio_previsto.required' => 'Informe o horário de início.',
            'fim_previsto.required' => 'Informe o horário de fim.',
            'fim_previsto.after' => 'O fim deve ser depois do início.',
            'tipo.required' => 'Selecione o caráter da cirurgia.',
            'tipo.in' => 'Caráter inválido.',
        ];
    }
}
