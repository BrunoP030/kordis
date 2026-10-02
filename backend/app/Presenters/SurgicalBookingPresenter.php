<?php

namespace App\Presenters;

use App\Models\SurgicalBooking;
use App\Models\SurgicalRoom;
use Illuminate\Support\Collection;

class SurgicalBookingPresenter
{
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
            'StartTime' => $booking->inicio_previsto->format('Y-m-d\TH:i:s'),
            'EndTime' => $booking->fim_previsto->format('Y-m-d\TH:i:s'),
            'RoomId' => $booking->surgical_room_id,
            'Status' => $booking->status_efetivo,
            'Cirurgiao' => $booking->cirurgiao,
            'Paciente' => $booking->paciente,
            'Observacoes' => $booking->observacoes,
        ];
    }

    /** @param Collection<int, SurgicalRoom> $salas */
    public function paraSalas(Collection $salas): array
    {
        return $salas->map(fn (SurgicalRoom $s) => ['Id' => $s->id, 'Nome' => $s->nome])->values()->all();
    }
}
