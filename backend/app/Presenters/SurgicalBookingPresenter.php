<?php

namespace App\Presenters;

use App\Models\SurgicalBooking;
use App\Models\SurgicalRoom;
use Illuminate\Support\Collection;

class SurgicalBookingPresenter
{
    private const FORMATO = 'Y-m-d\TH:i:s';

    /** @param Collection<int, SurgicalBooking> $bookings */
    public function paraAgenda(Collection $bookings): array
    {
        return $bookings->map(fn (SurgicalBooking $b) => $this->paraEvento($b))->values()->all();
    }

    public function paraEvento(SurgicalBooking $booking): array
    {
        return [
            'Id' => $booking->id,
            'Subject' => $booking->procedimento,
            'StartTime' => $booking->inicio_previsto->format(self::FORMATO),
            'EndTime' => $booking->fim_previsto->format(self::FORMATO),
            'RoomId' => $booking->surgical_room_id,
            'Status' => $booking->status_efetivo,
            'Cirurgiao' => $booking->cirurgiao,
            'Paciente' => $booking->paciente,
            'Observacoes' => $booking->observacoes,
            'Tipo' => $booking->tipo,
            'Porte' => $booking->porte,
            'Anestesia' => $booking->anestesia,
            'Anestesista' => $booking->anestesista,
            'EquipeApoio' => $booking->equipe_apoio,
            'Equipamentos' => $booking->equipamentos,
            'ReservaUti' => $booking->reserva_uti,
            'ReservaSangue' => $booking->reserva_sangue,
            'MotivoCancelamento' => $booking->motivo_cancelamento,
            'InicioReal' => $booking->inicio_real?->format(self::FORMATO),
            'FimReal' => $booking->fim_real?->format(self::FORMATO),
        ];
    }

    public function paraSala(SurgicalRoom $sala): array
    {
        return [
            'Id' => $sala->id,
            'Nome' => $sala->nome,
            'Especialidade' => $sala->especialidade,
            'Ativa' => $sala->ativa,
        ];
    }

    /** @param Collection<int, SurgicalRoom> $salas */
    public function paraSalas(Collection $salas): array
    {
        return $salas->map(fn (SurgicalRoom $s) => $this->paraSala($s))->values()->all();
    }
}
