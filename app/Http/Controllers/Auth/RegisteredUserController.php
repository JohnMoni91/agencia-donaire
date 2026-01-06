<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse; // <-- GARANTA QUE ESTA LINHA ESTEJA AQUI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // A CORREÇÃO ESTÁ AQUI: O TIPO DE RETORNO AGORA É 'RedirectResponse'
    // que corresponde ao 'use Illuminate\Http\RedirectResponse;' no topo.
    public function store(Request $request): RedirectResponse
        {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'documentos' => ['required', 'array', 'min:1'],
                'documentos.*' => ['required', 'file', 'mimes:pdf', 'max:2048'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'verification_status' => 'pendente', // Status inicial
            ]);

            if ($request->hasFile('documentos')) {
                foreach ($request->file('documentos') as $documento) {
                    $path = $documento->store('verification_documents', 'private');
                    $user->documentosVerificacao()->create(['caminho_documento' => $path]);
                }
            }

            event(new Registered($user));
            Auth::login($user);

            // Envia para o dashboard, que mostrará a tela de "aguardando"
            return redirect(route('dashboard'));
        }
}