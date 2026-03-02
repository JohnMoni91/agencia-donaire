<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function showUploadForm()
    {
        if (Auth::user()->verification_document_path) {
            return redirect()->route('dashboard')->with('error', 'Você já enviou um documento para análise.');
        }
        return view('auth.upload-verification');
    }

    public function storeUpload(Request $request)
    {
        $request->validate([
            'documento' => 'required|file|mimes:pdf|max:2048',
        ]);

        $path = $request->file('documento')->store('verification_documents', 'private');
        
        $user = Auth::user();
        $user->verification_document_path = $path;
        $user->verification_status = 'pendente'; 
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Documento enviado! Sua conta está em análise.');
    }

    public function resubmit(Request $request)
    {
        $request->validate([
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*' => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ]);

        $user = Auth::user();

        foreach ($user->documentosVerificacao as $docAntigo) {
            Storage::disk('private')->delete($docAntigo->caminho_documento);
        }
        $user->documentosVerificacao()->delete();

        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $documento) {
                $path = $documento->store('verification_documents', 'private');
                $user->documentosVerificacao()->create(['caminho_documento' => $path]);
            }
        }

        $user->verification_status = 'pendente';
        $user->verification_feedback = null;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Novos documentos enviados! Sua conta está em análise novamente.');
    }
}
