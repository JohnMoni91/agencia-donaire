<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modelo;
use Illuminate\Http\Request;

class ModeloApprovalController extends Controller
{
    // Mostra a lista de modelos pendentes
    public function index()
    {
        $modelos_pendentes = Modelo::where('status', 'pendente')->get();
        return view('admin.modelos_pendentes', compact('modelos_pendentes'));
    }

    // Ação de aprovar
    public function approve(Modelo $modelo)
    {
        $modelo->update(['status' => 'aprovado', 'feedback_admin' => null]);
        return redirect()->route('admin.modelos.pendentes')->with('success', 'Modelo aprovada!');
    }

    // Ação de reprovar
    public function reject(Request $request, Modelo $modelo)
    {
        $request->validate(['feedback_admin' => 'required|string|min:10']);

        $modelo->update([
            'status' => 'reprovado',
            'feedback_admin' => $request->feedback_admin
        ]);
        return redirect()->route('admin.modelos.pendentes')->with('success', 'Modelo devolvida para edição.');
    }
}