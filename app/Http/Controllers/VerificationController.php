<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    // Método para MOSTRAR o formulário de upload
    public function showUploadForm()
    {
        // Se o usuário já enviou o documento, não o deixe enviar de novo
        if (Auth::user()->verification_document_path) {
            return redirect()->route('dashboard')->with('error', 'Você já enviou um documento para análise.');
        }
        return view('auth.upload-verification');
    }

    // Método para SALVAR o documento enviado
    public function storeUpload(Request $request)
    {
        // 1. Valida o arquivo
        $request->validate([
            'documento' => 'required|file|mimes:pdf|max:2048', // Obrigatório, PDF, máx 2MB
        ]);

        // 2. Salva o arquivo em uma pasta privada
        // A pasta 'private' não é acessível publicamente pelo navegador, o que é mais seguro
        $path = $request->file('documento')->store('verification_documents', 'private');
        
        // 3. Atualiza o usuário no banco de dados
        $user = Auth::user();
        $user->verification_document_path = $path;
        $user->verification_status = 'pendente'; // Muda o status para pendente
        $user->save();

        // 4. Redireciona para o dashboard
        return redirect()->route('dashboard')->with('success', 'Documento enviado! Sua conta está em análise.');
    }

    public function resubmit(Request $request)
    {
        // 1. Valida os novos arquivos
        $request->validate([
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*' => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ]);

        $user = Auth::user();

        // 2. Apaga os documentos antigos para limpar o sistema
        foreach ($user->documentosVerificacao as $docAntigo) {
            // Apaga o arquivo físico do disco privado
            Storage::disk('private')->delete($docAntigo->caminho_documento);
        }
        // Apaga os registros do banco de dados
        $user->documentosVerificacao()->delete();

        // 3. Salva os novos documentos (mesma lógica do cadastro)
        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $documento) {
                $path = $documento->store('verification_documents', 'private');
                $user->documentosVerificacao()->create(['caminho_documento' => $path]);
            }
        }

        // 4. Atualiza o status do usuário de volta para pendente e limpa o feedback
        $user->verification_status = 'pendente';
        $user->verification_feedback = null;
        $user->save();

        // 5. Redireciona para o dashboard com mensagem de sucesso
        return redirect()->route('dashboard')->with('success', 'Novos documentos enviados! Sua conta está em análise novamente.');
    }
}