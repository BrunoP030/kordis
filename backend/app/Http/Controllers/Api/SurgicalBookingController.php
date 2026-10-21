<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurgicalBookingCancelRequest;
use App\Http\Requests\SurgicalBookingRequest;
use App\Http\Requests\SurgicalRoomRequest;
use App\Models\SurgicalBooking;
use App\Models\SurgicalRoom;
use App\Presenters\SurgicalBookingPresenter;
use App\Services\SurgicalBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurgicalBookingController extends Controller
{
    public function __construct(
        private readonly SurgicalBookingService $service,
        private readonly SurgicalBookingPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['data' => ['nullable', 'date_format:Y-m-d']], [
            'data.date_format' => 'Data inválida. Use o formato AAAA-MM-DD.',
        ]);

        $data = Carbon::parse($request->query('data', today()->toDateString()));

        return response()->json($this->presenter->paraAgenda($this->service->listarPorData($data)));
    }

    public function store(SurgicalBookingRequest $request): JsonResponse
    {
        return response()->json($this->presenter->paraEvento($this->service->criar($request->validated())), 201);
    }

    public function update(SurgicalBookingRequest $request, SurgicalBooking $surgicalBooking): JsonResponse
    {
        return $this->evento($this->service->atualizar($surgicalBooking, $request->validated()));
    }

    public function iniciar(SurgicalBooking $surgicalBooking): JsonResponse
    {
        return $this->evento($this->service->iniciar($surgicalBooking));
    }

    public function finalizar(SurgicalBooking $surgicalBooking): JsonResponse
    {
        return $this->evento($this->service->finalizar($surgicalBooking));
    }

    public function liberarSala(SurgicalBooking $surgicalBooking): JsonResponse
    {
        return $this->evento($this->service->liberarSala($surgicalBooking));
    }

    public function cancelar(SurgicalBookingCancelRequest $request, SurgicalBooking $surgicalBooking): JsonResponse
    {
        return $this->evento($this->service->cancelar($surgicalBooking, $request->validated('motivo')));
    }

    public function salas(Request $request): JsonResponse
    {
        // Salas inativas só interessam a quem gerencia o cadastro.
        $todas = $request->boolean('todas') && $request->user()->can('centro-cirurgico.gerenciar');

        return response()->json($this->presenter->paraSalas($this->service->listarSalas($todas)));
    }

    public function storeSala(SurgicalRoomRequest $request): JsonResponse
    {
        return response()->json($this->presenter->paraSala($this->service->criarSala($request->validated())), 201);
    }

    public function updateSala(SurgicalRoomRequest $request, SurgicalRoom $surgicalRoom): JsonResponse
    {
        return response()->json(
            $this->presenter->paraSala($this->service->atualizarSala($surgicalRoom, $request->validated()))
        );
    }

    private function evento(SurgicalBooking $booking): JsonResponse
    {
        return response()->json($this->presenter->paraEvento($booking));
    }
}
