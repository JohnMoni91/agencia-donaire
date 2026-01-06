<?php

namespace App\Http\Controllers;

use App\Models\Modelo; // Certifique-se que o nome do seu Model é 'Modelo'
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class ModelProfileController extends Controller
{
    public function create()
    {
        return view('site_modelo.create'); // ou o nome correto da sua view de cadastro
    }

    /**
     * ✅ MÉTODO STORE CORRIGIDO
     */
    public function store(Request $request)
    {
        // 1. VALIDAÇÃO CORRETA (baseada no seu formulário e no seu método update())
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'categoria' => 'required|string', // Campo obrigatório no seu formulário
            'altura_cm' => 'nullable|integer',
            'busto_torax' => 'nullable|integer',
            'cintura_cm' => 'nullable|integer',
            'quadril_cm' => 'nullable|integer',
            'manequim' => 'nullable|string|max:255',
            'sapatos' => 'nullable|string|max:255',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpeg,jpg,png|max:20480', // Limites do seu 'store' antigo
            'agenda_indisponivel' => 'nullable|string',
        ]);

        try {
            // 2. USAR O MODEL CORRETO (Modelo) E ADICIONAR user_id
            $modelo = Modelo::create([
                'user_id' => Auth::id(), // Adiciona o ID do usuário logado
                'nome' => $validatedData['nome'],
                'categoria' => $validatedData['categoria'],
                'status' => 'pendente', // Definir status inicial
                'altura_cm' => $validatedData['altura_cm'] ?? null,
                'busto_torax' => $validatedData['busto_torax'] ?? null,
                'cintura_cm' => $validatedData['cintura_cm'] ?? null,
                'quadril_cm' => $validatedData['quadril_cm'] ?? null,
                'manequim' => $validatedData['manequim'] ?? null,
                'sapatos' => $validatedData['sapatos'] ?? null,
            ]);

            // 3. Lógica de salvar FOTOS (adaptada do seu método update)
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $fotoNova) {
                    // Salva na pasta 'public/fotos_modelos'
                    $caminho = $fotoNova->store('fotos_modelos', 'public');
                    // Associa a foto ao modelo recém-criado
                    $modelo->fotos()->create(['caminho_foto' => $caminho]);
                }
            }

            // 4. Lógica de salvar AGENDA (adaptada do seu método update)
            if (!empty($validatedData['agenda_indisponivel'])) {
                $datasIndisponiveis = json_decode($validatedData['agenda_indisponivel']);
                if (is_array($datasIndisponiveis)) {
                    foreach ($datasIndisponiveis as $data) {
                        $modelo->agenda()->create(['data_indisponivel' => $data]);
                    }
                }
            }

            // 5. Redirecionar para o dashboard (igual ao seu 'update')
            // Certifique-se que você tem uma rota chamada 'dashboard'
            return redirect()->route('dashboard')
                            ->with('success', 'Perfil criado com sucesso e enviado para análise!');

        } catch (\Exception $e) {
            Log::error('Erro ao salvar novo perfil: '.$e->getMessage());
            return redirect()->back()
                            // ->withInput() salva os dados digitados para preencher o form de novo
                            ->withInput() 
                            ->with('error', 'Erro ao salvar perfil. Verifique os dados e tente novamente.');
        }
    }

    public function edit(Modelo $modelo)
    {
        // Seu código original - parece correto
        if ($modelo->user_id !== Auth::id() || $modelo->status !== 'reprovado') {
            abort(403, 'Acesso Não Autorizado ou Perfil Não Requer Edição');
        }
        return view('site_modelo.edit', compact('modelo'));
    }


    public function update(Request $request, Modelo $modelo)
    {
        // Seu código original - parece correto
        if ($modelo->user_id !== Auth::id()) {
            abort(403, 'Acesso Não Autorizado');
        }

        // Validação COMPLETA de todos os campos
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

        // Atualização COMPLETA de todos os campos
        $modelo->update([
            'nome' => $validatedData['nome'],
            'categoria' => $validatedData['categoria'],
            'status' => 'pendente', // Volta para pendente após edição
            'feedback_admin' => null,
            'altura_cm' => $validatedData['altura_cm'] ?? null,
            'busto_torax' => $validatedData['busto_torax'] ?? null,
            'cintura_cm' => $validatedData['cintura_cm'] ?? null,
            'quadril_cm' => $validatedData['quadril_cm'] ?? null,
            'manequim' => $validatedData['manequim'] ?? null,
            'sapatos' => $validatedData['sapatos'] ?? null,
        ]);
    
        // Lógica de update de fotos (já estava correta)
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

        // Lógica de update da agenda (já estava correta)
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

    // -------------------------------------------------------------------
    // ❗ MÉTODO "PORTEIRO" ATUALIZADO COM A VERIFICAÇÃO CORRETA ❗
    // -------------------------------------------------------------------
    /**
     * Verifica o status do perfil e direciona para a view correta.
     * Este método deve ser o alvo da sua rota 'dashboard'.
     */
    public function showDashboard()
    {
        $user = Auth::user();

        // ==========================================================
        // ⬇️ A CORREÇÃO DEFINITIVA ESTÁ AQUI ⬇️
        // ==========================================================
        
        // Verificamos PRIMEIRO se o usuário é um admin usando a regra
        // que descobrimos que funciona (is_admin == 1).
        if ($user && $user->is_admin == 1) { 
            // Se for admin, redireciona IMEDIATAMENTE para o painel de admin.
            return redirect()->route('admin.dashboard');
        }

        // ==========================================================
        // ⬆️ FIM DA CORREÇÃO ⬆️
        // ==========================================================

        // O resto do seu código original continua inalterado.
        // Este código só será executado se o usuário NÃO for admin.

        // Estou assumindo que no seu Model User existe a relação "modelo"
        // ex: public function modelo() { return $this->hasOne(Modelo::class); }
        $modelo = $user->modelo;

        // Caso 1: Usuário ainda não tem perfil
        if (!$modelo) {
            // A página "Olá, Donaire Admin!" provavelmente é esta view 'bem-vindo'.
            // Usuários normais verão isso e poderão criar um perfil.
            return view('bem-vindo'); // ou 'site_modelo.create'
        }

        // Caso 2: Perfil PENDENTE -> Mostra a págima "pendente.blade.php"
        if ($modelo->status === 'pendente') {
            // Você confirmou que o nome é 'pendente.blade.php'
            // Assumindo que está na pasta 'site_modelo'
            return view('site_modelo.pendente');
        }

        // Caso 3: Perfil REPROVADO -> Manda editar
        if ($modelo->status === 'reprovado') {
            return redirect()->route('modelos.edit', $modelo->id)
                             ->with('feedback', $modelo->feedback_admin);
        }

        // Caso 4: Perfil APROVADO -> Mostra o dashboard real
        // Certifique-se que sua view do dashboard se chama 'dashboard.blade.php'
        return view('dashboard', compact('modelo'));
    }
}