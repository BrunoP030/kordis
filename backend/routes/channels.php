<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Cards de higienização: qualquer um com acesso ao dashboard (inclui a equipe de limpeza).
Broadcast::channel('tarefas.higienizacao', fn ($user) => $user->can('dashboard.visualizar'));

// Todos os cards: fora do alcance de quem só tem o perfil de limpeza.
Broadcast::channel('tarefas', fn ($user) => $user->can('dashboard.visualizar') && ! $user->apenasLimpeza());

Broadcast::channel('centro-cirurgico', function ($user) {
    return $user->can('centro-cirurgico.visualizar');
});
