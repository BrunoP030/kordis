<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

// ShouldBroadcastNow: transmite na hora, sem depender de um queue worker rodando.
class SurgicalBookingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /** @param array $reserva booking já formatado pelo SurgicalBookingPresenter */
    public function __construct(public array $reserva) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('centro-cirurgico')];
    }

    public function broadcastAs(): string
    {
        return 'reserva.atualizada';
    }
}
