<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('centro-cirurgico', function ($user) {
    return $user->can('centro-cirurgico.visualizar');
});
