<?php

namespace App\Events;

use App\Models\NursingTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

// ShouldBroadcastNow: transmite na hora, sem depender de um queue worker rodando.
class NursingTaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /** @param array $tarefa card já formatado pelo NursingTaskPresenter */
    public function __construct(public array $tarefa, private readonly string $categoria) {}

    public function broadcastOn(): array
    {
        // Cards de higienização também vão para o canal da equipe de limpeza.
        $canais = [new PrivateChannel('tarefas')];

        if ($this->categoria === NursingTask::CATEGORIA_HIGIENIZACAO) {
            $canais[] = new PrivateChannel('tarefas.higienizacao');
        }

        return $canais;
    }

    public function broadcastAs(): string
    {
        return 'tarefa.atualizada';
    }
}
