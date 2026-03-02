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
        $query = Modelo::where('status', 'pendente');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->search . '%');
        }

        $modelos_pendentes = $query->with(['user', 'fotos'])->get();
        return view('admin.dashboard', compact('modelos_pendentes'));
    }

    public function aprovadas(Request $request)
    {
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

    public function reprovadas(Request $request)
    {
        $query = Modelo::where('status', 'reprovado');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->search . '%');
        }

        $modelos_reprovados = $query->with('user', 'fotos')->get();
        
        return view('admin.reprovadas', compact('modelos_reprovados'));
    }

    public function show(Modelo $modelo)
    {
        $modelo->load(['user', 'fotos', 'agenda' => function ($query) {
            $query->where('data_indisponivel', '>=', now()->startOfDay());
        }]);

        return view('admin.show', compact('modelo'));
    }

    public function approve(Modelo $modelo)
    {
        $modelo->status = 'aprovado';
        $modelo->feedback_admin = null;
        $modelo->save();
        return redirect()->route('admin.dashboard')->with('success', 'Perfil aprovado com sucesso!');
    }

    public function reject(Request $request, Modelo $modelo)
    {
        $request->validate(['feedback_admin' => 'required|string|min:5']);

        $modelo->status = 'reprovado';
        $modelo->feedback_admin = $request->feedback_admin;
        $modelo->save();

        return redirect()->route('admin.dashboard')->with('success', 'Perfil devolvido para edição com sucesso.');
    }

    public function updateProfileByAdmin(Request $request, Modelo $modelo)
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'altura_cm' => 'nullable|integer|min:0',
            'busto_cm' => 'nullable|integer|min:0',
            'cintura_cm' => 'nullable|integer|min:0',
            'quadril_cm' => 'nullable|integer|min:0',
            'manequim' => 'nullable|string|max:255',
            'sapatos' => 'nullable|string|max:255',
            'cor_cabelo' => 'nullable|string|max:255',
            'cor_olhos' => 'nullable|string|max:255',
        ]);

        $modelo->update($validatedData);

        return redirect()->back()->with('success', 'Dados do perfil atualizados com sucesso!');
    }

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

    public function downloadDocument(DocumentoVerificacao $documento)
    {
        if (!Storage::disk('private')->exists($documento->caminho_documento)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return Storage::disk('private')->download($documento->caminho_documento);
    }

    public function approveUser(User $user)
    {
        $user->verification_status = 'aprovado';
        $user->verification_feedback = null;
        $user->save();

        return redirect()->route('admin.user.verifications')->with('success', 'Conta da usuária aprovada com sucesso!');
    }

    public function rejectUser(Request $request, User $user)
    {
        $request->validate(['feedback' => 'required|string|min:10']);

        $user->verification_status = 'rejeitado';
        $user->verification_feedback = $request->feedback;
        $user->save();

        return redirect()->route('admin.user.verifications')->with('success', 'Conta da usuária rejeitada.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Você não pode deletar sua própria conta.');
        }
        
        $user->delete(); // Isso também deletará perfis, fotos, etc., por causa do onDelete('cascade')
        return redirect()->back()->with('success', 'Conta da usuária deletada permanentemente.');
    }

}
