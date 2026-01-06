<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modelo;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    /**
     * Mostra a página inicial com modelos em destaque.
     */
    public function home()
    {
        // Pega até 8 modelos APROVADOS de forma aleatória
        $modelos_destaque = Modelo::where('status', 'aprovado')
                                  ->with('fotos') // Otimiza a busca carregando as fotos
                                  ->inRandomOrder()
                                  ->limit(8)
                                  ->get();
        
        return view('home', ['modelos' => $modelos_destaque]);
    }

    /**
     * Mostra a página "Sobre".
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Mostra a página de "Contato".
     */
    public function contato()
    {
        return view('contact');
    }
    
    /**
     * Mostra o perfil detalhado de um modelo específico.
     */
    public function showModelProfile(Modelo $modelo)
    {
        // Garante que apenas modelos aprovados sejam visíveis publicamente
        if ($modelo->status !== 'aprovado') {
            abort(404);
        }

        // Carrega as fotos e a agenda futura do modelo
        $modelo->load(['fotos', 'agenda' => fn($q) => $q->where('data_indisponivel', '>=', now())]);
        
        return view('detalhes-modelo', compact('modelo'));
    }

    /**
     * ATUALIZADO: Controla a página de casting com filtros avançados.
     * Constrói uma query dinâmica baseada nos filtros enviados pelo formulário.
     */
    public function casting(Request $request)
    {
        // 1. Inicia a query base, buscando apenas modelos com status 'aprovado'
        $query = Modelo::query()->where('status', 'aprovado');

        // 2. Aplica os filtros um a um, se eles existirem no request (URL)

        // Filtro por termo de busca (nome)
        if ($request->filled('search')) {
            $query->where('nome', 'LIKE', '%' . $request->input('search') . '%');
        }

        // Filtro por categoria
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        // Filtro por altura mínima (em cm)
        if ($request->filled('altura_min')) {
            $query->where('altura', '>=', $request->input('altura_min'));
        }

        // Filtro por altura máxima (em cm)
        if ($request->filled('altura_max')) {
            $query->where('altura', '<=', $request->input('altura_max'));
        }

        // Filtros por medidas mínimas (em cm)
        // Certifique-se que os nomes das colunas no seu banco de dados correspondem (ex: 'busto', 'cintura', 'quadril')
        if ($request->filled('busto_min')) {
            $query->where('busto', '>=', $request->input('busto_min'));
        }

        if ($request->filled('cintura_min')) {
            $query->where('cintura', '>=', $request->input('cintura_min'));
        }

        if ($request->filled('quadril_min')) {
            $query->where('quadril', '>=', $request->input('quadril_min'));
        }

        // 3. Define um título dinâmico para a página
        $titulo = 'Nosso Casting';
        if ($request->filled('categoria')) {
            $titulo = 'Casting ' . $request->input('categoria');
        } elseif ($request->anyFilled(['search', 'altura_min', 'altura_max'])) {
            $titulo = 'Resultados do Casting';
        }

        // 4. Executa a query final
        //  - Carrega as fotos para evitar o problema N+1 (otimização)
        //  - Ordena os resultados por nome
        //  - Pagina os resultados para não sobrecarregar a página
        $modelos = $query->with('fotos')->orderBy('nome', 'asc')->paginate(16); // 16 modelos por página

        // 5. Retorna a view 'casting' com os dados necessários
        return view('casting', [
            'titulo' => $titulo,
            'modelos' => $modelos,
        ]);
    }
    
    /**
     * Mostra a página "d-connect".
     */
    public function dConnect()
    {
        return view('d-connect');
    }
}