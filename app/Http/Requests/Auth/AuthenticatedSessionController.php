<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- ADICIONE ESTA LINHA
use Illuminate\View\View;

// CÓDIGO NOVO (para colocar no lugar da linha acima):

// Verifica se o usuário que acabou de logar é um admin.
if (auth()->user()->is_admin) {
    // Se for, redireciona para a rota do dashboard do admin.
    return redirect()->route('admin.dashboard');
}

// Se não for admin, segue o fluxo normal e redireciona para o dashboard padrão.
return redirect()->intended(RouteServiceProvider::HOME);