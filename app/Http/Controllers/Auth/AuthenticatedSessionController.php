<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        \Log::info('Usuário logado: ' . $user->id . ', Role: ' . $user->role);

        // Verificar se o usuário é um administrador (usando a propriedade is_admin)
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        // Verificar se o usuário é uma modelo (role === 0)
        if ($user->role === 0) {
            // Carrega a relação 'modelo' para acessar o status
            $modelo = $user->modelo;

            \Log::info('Modelo ID: ' . ($modelo ? $modelo->id : 'null') . ', Status: ' . ($modelo ? $modelo->status : 'null'));

            if ($modelo && $modelo->status === 'reprovado') {
                return redirect()->route('modelos.edit', $modelo->id);
            } else {
                return redirect()->route('dashboard'); // Redireciona para o dashboard (ou página de espera) se o status não for 'reprovado'
            }
        }

        // Caso o role não seja nem admin nem modelo, redireciona para a página inicial pública
        return redirect('/bem-vindo');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/bem-vindo');
    }
}