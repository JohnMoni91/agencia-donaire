<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Garante que o usuário está autenticado e é admin
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }

        // Redireciona se não for admin
        return redirect('/')->with('error', 'Acesso não autorizado.');
    }
}
