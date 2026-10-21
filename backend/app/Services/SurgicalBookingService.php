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
    /** Status que liberam a sala (cirurgia cancelada não ocupa horário). */
    private const STATUS_LIBERADOS = ['cancelado'];

    public function __construct(private readonly SurgicalBookingPresenter $presenter) {}

    /** Cirurgias do dia (status "atrasado" é calculado na leitura). */
    public function listarPorData(Carbon $data): Collection
    {
        return SurgicalBooking::query()
            ->whereDate('inicio_previsto', $data->toDateString())
            ->orderBy('inicio_previsto')
            ->get();
    }

    public function listarSalas(bool $incluirInativas = false): Collection
    {
        return SurgicalRoom::query()
            ->when(! $incluirInativas, fn ($q) => $q->where('ativa', true))
            ->orderBy('nome')
            ->get();
    }

    public function temConflito(int $salaId, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): bool
    {
        return SurgicalBooking::query()
            ->where('surgical_room_id', $salaId)
            ->whereNotIn('status', self::STATUS_LIBERADOS)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where('inicio_previsto', '<', $fim)
            ->where('fim_previsto', '>', $inicio)
            ->exists();
    }

    public function criar(array $dados): SurgicalBooking
    {
        $dados['criado_por'] = Auth::id();

        return $this->publicar(SurgicalBooking::create($dados)->refresh());
    }

    public function atualizar(SurgicalBooking $booking, array $dados): SurgicalBooking
    {
        $this->exigirStatus($booking, ['agendado'], 'Só é possível editar cirurgias agendadas.');
        $booking->update($dados);

        return $this->publicar($booking->refresh());
    }

    public function iniciar(SurgicalBooking $booking): SurgicalBooking
    {
        $this->exigirStatus($booking, ['agendado'], 'Só é possível iniciar cirurgias agendadas.');
        $booking->update(['status' => 'em_andamento', 'inicio_real' => now()]);

        return $this->publicar($booking->refresh());
    }

    /** Encerra a cirurgia e coloca a sala em limpeza. */
    public function finalizar(SurgicalBooking $booking): SurgicalBooking
    {
        $this->exigirStatus($booking, ['em_andamento'], 'Só é possível finalizar cirurgias em andamento.');
        $booking->update(['status' => 'limpeza', 'fim_real' => now()]);

        return $this->publicar($booking->refresh());
    }

    public function liberarSala(SurgicalBooking $booking): SurgicalBooking
    {
        $this->exigirStatus($booking, ['limpeza'], 'A sala não está em limpeza.');
        $booking->update(['status' => 'concluido']);

        return $this->publicar($booking->refresh());
    }

    public function cancelar(SurgicalBooking $booking, string $motivo): SurgicalBooking
    {
        $this->exigirStatus($booking, ['agendado'], 'Só é possível cancelar cirurgias agendadas.');
        $booking->update(['status' => 'cancelado', 'motivo_cancelamento' => $motivo]);

        return $this->publicar($booking->refresh());
    }

    public function criarSala(array $dados): SurgicalRoom
    {
        return SurgicalRoom::create($dados);
    }

    public function atualizarSala(SurgicalRoom $sala, array $dados): SurgicalRoom
    {
        $sala->update($dados);

        return $sala;
    }

    private function exigirStatus(SurgicalBooking $booking, array $permitidos, string $mensagem): void
    {
        if (! in_array($booking->status, $permitidos, true)) {
            throw ValidationException::withMessages(['status' => $mensagem]);
        }
    }

    private function publicar(SurgicalBooking $booking): SurgicalBooking
    {
        // O dado já está salvo: se o servidor de WebSocket estiver fora, só registra o erro
        // (as telas abertas se atualizam no próximo carregamento) em vez de falhar a requisição.
        try {
            SurgicalBookingUpdated::dispatch($this->presenter->paraEvento($booking));
        } catch (\Throwable $e) {
            report($e);
        }

        return $booking;
    }
}
