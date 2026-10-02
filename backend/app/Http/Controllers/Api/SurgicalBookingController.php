<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurgicalBookingRequest;
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

        return response()->json(
            $this->presenter->paraAgenda($this->service->listarPorData($data)->flatten())
        );
    }

    public function salas(): JsonResponse
    {
        return response()->json($this->presenter->paraSalas($this->service->listarSalas()));
    }

    public function store(SurgicalBookingRequest $request): JsonResponse
    {
        $booking = $this->service->criar($request->validated());

        return response()->json($this->presenter->paraEvento($booking), 201);
    }
}
