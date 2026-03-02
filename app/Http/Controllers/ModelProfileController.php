<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class ModelProfileController extends Controller
{
    public function create()
    {
        return view('site_modelo.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'categoria' => 'required|string', 
            'altura_cm' => 'nullable|integer',
            'busto_torax' => 'nullable|integer',
            'cintura_cm' => 'nullable|integer',
            'quadril_cm' => 'nullable|integer',
            'manequim' => 'nullable|string|max:255',
            'sapatos' => 'nullable|string|max:255',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpeg,jpg,png|max:20480', 
            'agenda_indisponivel' => 'nullable|string',
        ]);

        try {
            $modelo = Modelo::create([
                'user_id' => Auth::id(), 
                'nome' => $validatedData['nome'],
                'categoria' => $validatedData['categoria'],
                'status' => 'pendente', 
                'altura_cm' => $validatedData['altura_cm'] ?? null,
                'busto_torax' => $validatedData['busto_torax'] ?? null,
                'cintura_cm' => $validatedData['cintura_cm'] ?? null,
                'quadril_cm' => $validatedData['quadril_cm'] ?? null,
                'manequim' => $validatedData['manequim'] ?? null,
                'sapatos' => $validatedData['sapatos'] ?? null,
            ]);

            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $fotoNova) {
                    $caminho = $fotoNova->store('fotos_modelos', 'public');
                    $modelo->fotos()->create(['caminho_foto' => $caminho]);
                }
            }

            if (!empty($validatedData['agenda_indisponivel'])) {
                $datasIndisponiveis = json_decode($validatedData['agenda_indisponivel']);
                if (is_array($datasIndisponiveis)) {
                    foreach ($datasIndisponiveis as $data) {
                        $modelo->agenda()->create(['data_indisponivel' => $data]);
                    }
                }
            }

            return redirect()->route('dashboard')
                            ->with('success', 'Perfil criado com sucesso e enviado para análise!');

        } catch (\Exception $e) {
            Log::error('Erro ao salvar novo perfil: '.$e->getMessage());
            return redirect()->back()
                            ->withInput() 
                            ->with('error', 'Erro ao salvar perfil. Verifique os dados e tente novamente.');
        }
    }

    public function edit(Modelo $modelo)
    {
        if ($modelo->user_id !== Auth::id() || $modelo->status !== 'reprovado') {
            abort(403, 'Acesso Não Autorizado ou Perfil Não Requer Edição');
        }
        return view('site_modelo.edit', compact('modelo'));
    }


    public function update(Request $request, Modelo $modelo)
    {
        if ($modelo->user_id !== Auth::id()) {
            abort(403, 'Acesso Não Autorizado');
        }

        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'categoria' => 'required|string',
            'altura_cm' => 'nullable|integer',
            'busto_torax' => 'nullable|integer',
            'cintura_cm' => 'nullable|integer',
            'quadril_cm' => 'nullable|integer',
            'manequim' => 'nullable|string|max:255',
            'sapatos' => 'nullable|string|max:255',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image',
            'agenda_indisponivel' => 'nullable|string',
        ]);

        $modelo->update([
            'nome' => $validatedData['nome'],
            'categoria' => $validatedData['categoria'],
            'status' => 'pendente', 
            'feedback_admin' => null,
            'altura_cm' => $validatedData['altura_cm'] ?? null,
            'busto_torax' => $validatedData['busto_torax'] ?? null,
            'cintura_cm' => $validatedData['cintura_cm'] ?? null,
            'quadril_cm' => $validatedData['quadril_cm'] ?? null,
            'manequim' => $validatedData['manequim'] ?? null,
            'sapatos' => $validatedData['sapatos'] ?? null,
        ]);

        if ($request->hasFile('fotos')) {
            foreach ($modelo->fotos as $fotoAntiga) {
                Storage::disk('public')->delete($fotoAntiga->caminho_foto);
            }
            $modelo->fotos()->delete();
            foreach ($request->file('fotos') as $fotoNova) {
                $caminho = $fotoNova->store('fotos_modelos', 'public');
                $modelo->fotos()->create(['caminho_foto' => $caminho]);
            }
        }

        $modelo->agenda()->delete();
        if (!empty($validatedData['agenda_indisponivel'])) {
            $datasIndisponiveis = json_decode($validatedData['agenda_indisponivel']);
            if (is_array($datasIndisponiveis)) {
                foreach ($datasIndisponiveis as $data) {
                    $modelo->agenda()->create(['data_indisponivel' => $data]);
                }
            }
        }

        return redirect()->route('dashboard')->with('success', 'Perfil atualizado e reenviado para análise!');
    }

    public function showDashboard()
    {
        $user = Auth::user();

        if ($user && $user->is_admin == 1) { 
            return redirect()->route('admin.dashboard');
        }

        $modelo = $user->modelo;

        if (!$modelo) {
            return view('bem-vindo');
        }

        if ($modelo->status === 'pendente') {
            return view('site_modelo.pendente');
        }

        if ($modelo->status === 'reprovado') {
            return redirect()->route('modelos.edit', $modelo->id)
                             ->with('feedback', $modelo->feedback_admin);
        }

        return view('dashboard', compact('modelo'));
    }
}
