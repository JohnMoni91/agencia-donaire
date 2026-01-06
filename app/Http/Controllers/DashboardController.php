<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Modelo; // Importe o Modelo para a verificação

class DashboardController extends Controller
{
    /**
     * Direciona o usuário para o painel correto após o login.
     * VERSÃO FINAL E COMPLETA
     */
// Em DashboardController.php

        public function index()
        {
            $user = Auth::user();

            // 1. É um admin? Se sim, vai para o painel de admin.
            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }

            // --- NOVA LÓGICA DE VERIFICAÇÃO PARA MODELOS ---

            // 2. A conta da modelo já foi verificada pelo admin?
            switch ($user->verification_status) {
                case 'pendente':
                    // Se a conta está pendente, mostra a tela de "Aguardando Verificação"
                    return view('auth.verification-pending');

                case 'rejeitado':
                    // Se a conta foi rejeitada, mostra a tela com o feedback do admin
                    return view('auth.verification-rejected', ['feedback' => $user->verification_feedback]);

                case 'aprovado':
                    // SÓ SE A CONTA FOR APROVADA, nós continuamos para a lógica de criação de perfil
                    $modelo = $user->modelo;
                    if (!$modelo) {
                        // Se a conta foi aprovada mas ela ainda não tem perfil, manda criar
                        return redirect()->route('modelos.create');
                    } else {
                        // Se ela já tem perfil, usa a lógica de status do perfil que já tínhamos
                        switch ($modelo->status) {
                            case 'aprovado': return view('site_modelo.aprovado', ['modelo' => $modelo]);
                            case 'pendente': return view('site_modelo.pendente');
                            case 'reprovado': return redirect()->route('modelos.edit', $modelo->id);
                        }
                    }
                    break;
            }

            // Um fallback de segurança
            return redirect('/');
        }
    }