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

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        \Log::info('Usuário logado: ' . $user->id . ', Role: ' . $user->role);

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 0) {
            $modelo = $user->modelo;

            \Log::info('Modelo ID: ' . ($modelo ? $modelo->id : 'null') . ', Status: ' . ($modelo ? $modelo->status : 'null'));

            if ($modelo && $modelo->status === 'reprovado') {
                return redirect()->route('modelos.edit', $modelo->id);
            } else {
                return redirect()->route('dashboard');
            }
        }

        return redirect('/bem-vindo');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/bem-vindo');
    }
}
