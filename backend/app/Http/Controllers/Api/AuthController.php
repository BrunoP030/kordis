<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credenciais = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credenciais, remember: true)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        $request->session()->regenerate();

        return response()->json([
            'usuario' => Auth::user()->only('id', 'name', 'email'),
            ...Auth::user()->permissoesParaFrontend(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['mensagem' => 'Sessão encerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => $request->user()->only('id', 'name', 'email'),
            ...$request->user()->permissoesParaFrontend(),
        ]);
    }
}
