<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modelo;
use Illuminate\Support\Str;

class PublicController extends Controller
{

    public function home()
    {
        $modelos_destaque = Modelo::where('status', 'aprovado')
                                  ->with('fotos')
                                  ->inRandomOrder()
                                  ->limit(8)
                                  ->get();
        
        return view('home', ['modelos' => $modelos_destaque]);
    }

    public function about()
    {
        return view('about');
    }

    public function contato()
    {
        return view('contact');
    }

    public function showModelProfile(Modelo $modelo)
    {
        if ($modelo->status !== 'aprovado') {
            abort(404);
        }

        $modelo->load(['fotos', 'agenda' => fn($q) => $q->where('data_indisponivel', '>=', now())]);
        
        return view('detalhes-modelo', compact('modelo'));
    }

    public function casting(Request $request)
    {
        $query = Modelo::query()->where('status', 'aprovado');

        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->input('search') . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        if ($request->filled('altura_min')) {
            $query->where('altura', '>=', $request->input('altura_min'));
        }

        if ($request->filled('altura_max')) {
            $query->where('altura', '<=', $request->input('altura_max'));
        }

        if ($request->filled('busto_min')) {
            $query->where('busto', '>=', $request->input('busto_min'));
        }

        if ($request->filled('cintura_min')) {
            $query->where('cintura', '>=', $request->input('cintura_min'));
        }

        if ($request->filled('quadril_min')) {
            $query->where('quadril', '>=', $request->input('quadril_min'));
        }

        $titulo = 'Nosso Casting';
        if ($request->filled('categoria')) {
            $titulo = 'Casting ' . $request->input('categoria');
        } elseif ($request->anyFilled(['search', 'altura_min', 'altura_max'])) {
            $titulo = 'Resultados do Casting';
        }

        $modelos = $query->with('fotos')->orderBy('nome', 'asc')->paginate(16); 

        return view('casting', [
            'titulo' => $titulo,
            'modelos' => $modelos,
        ]);
    }
    
    public function dConnect()
    {
        return view('d-connect');
    }
}
