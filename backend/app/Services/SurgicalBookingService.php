<?php

namespace App\Services;

use App\Events\SurgicalBookingUpdated;
use App\Models\SurgicalBooking;
use App\Models\SurgicalRoom;
use App\Presenters\SurgicalBookingPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SurgicalBookingService
{
    public function __construct(private readonly SurgicalBookingPresenter $presenter) {}

    /** Reservas do dia agrupadas por sala (status "atrasado" é calculado na leitura). */
    public function listarPorData(Carbon $data): \Illuminate\Support\Collection
    {
        return SurgicalBooking::query()
            ->whereDate('inicio_previsto', $data->toDateString())
            ->orderBy('inicio_previsto')
            ->get()
            ->groupBy('surgical_room_id');
    }

    public function listarSalas(): Collection
    {
        return SurgicalRoom::query()->where('ativa', true)->orderBy('nome')->get();
    }

    public function temConflito(int $salaId, Carbon $inicio, Carbon $fim): bool
    {
        return SurgicalBooking::query()
            ->where('surgical_room_id', $salaId)
            ->where('inicio_previsto', '<', $fim)
            ->where('fim_previsto', '>', $inicio)
            ->exists();
    }

    public function criar(array $dados): SurgicalBooking
    {
        if ($this->temConflito(
            $dados['surgical_room_id'],
            Carbon::parse($dados['inicio_previsto']),
            Carbon::parse($dados['fim_previsto']),
        )) {
            throw ValidationException::withMessages([
                'inicio_previsto' => 'Já existe uma reserva nesta sala neste horário.',
            ]);
        }

        $dados['criado_por'] = Auth::id();
        $booking = SurgicalBooking::create($dados)->refresh();

        SurgicalBookingUpdated::dispatch($this->presenter->paraEvento($booking));

        return $booking;
    }
}
