<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modelo;
use App\Models\User;
use App\Models\DocumentoVerificacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{

    public function dashboard(Request $request)
    {
        // Começa a query base
        $query = Modelo::where('status', 'pendente');

        // Aplica o filtro de CATEGORIA, se existir na URL
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Aplica o filtro de BUSCA POR NOME, se existir na URL
        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->search . '%');
        }

        // Executa a query e envia os dados para a view
        $modelos_pendentes = $query->with(['user', 'fotos'])->get();
        return view('admin.dashboard', compact('modelos_pendentes'));
    }

    /**
     * Mostra a lista de modelos APROVADAS, com filtros.
     */
    public function aprovadas(Request $request)
    {
        // A lógica é exatamente a mesma, só muda o status
        $query = Modelo::where('status', 'aprovado');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->search . '%');
        }

        $modelos_aprovadas = $query->with('user', 'fotos')->get();
        return view('admin.aprovadas', compact('modelos_aprovadas'));
    }

    /**
     * ✅ NOVA FUNÇÃO: Mostra a lista de modelos EM EDIÇÃO (Reprovadas), com filtros.
     */
    public function reprovadas(Request $request)
    {
        // Busca apenas quem foi devolvido para edição
        $query = Modelo::where('status', 'reprovado');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->search . '%');
        }

        $modelos_reprovados = $query->with('user', 'fotos')->get();
        
        // Retorna a view que criamos (admin/reprovadas.blade.php)
        return view('admin.reprovadas', compact('modelos_reprovados'));
    }

    /**
     * Mostra a página de detalhes de um único perfil de modelo para análise.
     */
    public function show(Modelo $modelo)
    {
        $modelo->load(['user', 'fotos', 'agenda' => function ($query) {
            $query->where('data_indisponivel', '>=', now()->startOfDay());
        }]);

        return view('admin.show', compact('modelo'));
    }

    /**
     * Aprova o perfil de uma modelo, mudando seu status no banco.
     */
    public function approve(Modelo $modelo)
    {
        $modelo->status = 'aprovado';
        $modelo->feedback_admin = null;
        $modelo->save();
        return redirect()->route('admin.dashboard')->with('success', 'Perfil aprovado com sucesso!');
    }

    /**
     * Reprova o perfil de uma modelo e salva o motivo (feedback).
     */
    public function reject(Request $request, Modelo $modelo)
    {
        $request->validate(['feedback_admin' => 'required|string|min:5']);

        $modelo->status = 'reprovado';
        $modelo->feedback_admin = $request->feedback_admin;
        $modelo->save();

        return redirect()->route('admin.dashboard')->with('success', 'Perfil devolvido para edição com sucesso.');
    }

    /**
     * Atualiza os dados de texto (nome, bio) de um perfil.
     */
    public function updateProfileByAdmin(Request $request, Modelo $modelo)
    {
        // Valida os dados de texto e as novas medidas que o admin enviou
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'bio' => 'nullable|string',
            // Adicionando validação para as medidas
            'altura_cm' => 'nullable|integer|min:0',
            'busto_cm' => 'nullable|integer|min:0',
            'cintura_cm' => 'nullable|integer|min:0',
            'quadril_cm' => 'nullable|integer|min:0',
            'manequim' => 'nullable|string|max:255',
            'sapatos' => 'nullable|string|max:255',
            'cor_cabelo' => 'nullable|string|max:255',
            'cor_olhos' => 'nullable|string|max:255',
        ]);

        // O método update já recebe todos os dados validados e salva de uma vez.
        // Isso funciona porque já adicionamos todos esses campos ao $fillable do Model.
        $modelo->update($validatedData);

        // Redireciona de volta para a mesma página com uma mensagem de sucesso.
        return redirect()->back()->with('success', 'Dados do perfil atualizados com sucesso!');
    }

    /**
     * Atualiza apenas a agenda de uma modelo.
     */
    public function updateAgenda(Request $request, Modelo $modelo)
    {
        $request->validate(['agenda_indisponivel' => 'nullable|string']);
        $modelo->agenda()->delete();

        if (!empty($request->agenda_indisponivel)) {
            $datasIndisponiveis = json_decode($request->agenda_indisponivel);
            if (is_array($datasIndisponiveis)) {
                foreach ($datasIndisponiveis as $data) {
                    $modelo->agenda()->create(['data_indisponivel' => $data]);
                }
            }
        }
        return redirect()->back()->with('success', 'Agenda da modelo atualizada com sucesso!');
    }

    /**
     * Deleta permanentemente o perfil de uma modelo.
     */
    public function destroy(Modelo $modelo)
    {
        $modelo->delete();
        return redirect()->route('admin.dashboard')->with('success', 'Perfil da modelo deletado com sucesso.');
    }

    public function showVerifications()
    {
        $pending_users = User::where('is_admin', false)
                               ->where('verification_status', 'pendente')
                               ->with('documentosVerificacao')
                               ->get();
                               
        return view('admin.verifications', compact('pending_users'));
    }

    /**
     * Permite o download seguro de um documento de verificação.
     */
    public function downloadDocument(DocumentoVerificacao $documento)
    {
        // Garante que o arquivo existe antes de tentar o download
        if (!Storage::disk('private')->exists($documento->caminho_documento)) {
            abort(404, 'Arquivo não encontrado.');
        }
        // Retorna o arquivo para o navegador do admin como um download
        return Storage::disk('private')->download($documento->caminho_documento);
    }

    /**
     * Aprova a conta de uma usuária.
     */
    public function approveUser(User $user)
    {
        $user->verification_status = 'aprovado';
        $user->verification_feedback = null;
        $user->save();

        // No futuro, aqui você pode disparar um e-mail para a modelo
        return redirect()->route('admin.user.verifications')->with('success', 'Conta da usuária aprovada com sucesso!');
    }

    /**
     * Rejeita a conta de uma usuária com um motivo.
     */
    public function rejectUser(Request $request, User $user)
    {
        $request->validate(['feedback' => 'required|string|min:10']);

        $user->verification_status = 'rejeitado';
        $user->verification_feedback = $request->feedback;
        $user->save();
        
        // No futuro, aqui você pode disparar um e-mail de rejeição
        return redirect()->route('admin.user.verifications')->with('success', 'Conta da usuária rejeitada.');
    }
    
    /**
     * Deleta permanentemente a conta de uma usuária.
     */
    public function destroyUser(User $user)
    {
        // Trava de segurança para não se deletar por engano
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Você não pode deletar sua própria conta.');
        }
        
        $user->delete(); // Isso também deletará perfis, fotos, etc., por causa do onDelete('cascade')
        return redirect()->back()->with('success', 'Conta da usuária deletada permanentemente.');
    }

}