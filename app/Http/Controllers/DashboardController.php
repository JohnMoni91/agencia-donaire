<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Modelo; 

class DashboardController extends Controller
{
        public function index()
        {
            $user = Auth::user();

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }

            switch ($user->verification_status) {
                case 'pendente':
                    return view('auth.verification-pending');

                case 'rejeitado':
                    return view('auth.verification-rejected', ['feedback' => $user->verification_feedback]);

                case 'aprovado':
                    $modelo = $user->modelo;
                    if (!$modelo) {
                        return redirect()->route('modelos.create');
                    } else {
                        switch ($modelo->status) {
                            case 'aprovado': return view('site_modelo.aprovado', ['modelo' => $modelo]);
                            case 'pendente': return view('site_modelo.pendente');
                            case 'reprovado': return redirect()->route('modelos.edit', $modelo->id);
                        }
                    }
                    break;
            }

            return redirect('/');
        }
    }
