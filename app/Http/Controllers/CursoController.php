<?php

namespace App\Http\Controllers;



use App\Models\Categorias;
use App\Models\Cursos;
use App\Models\Matriculas;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index(Request $request)
    {
        $query = Cursos::with(['categoria','instrutor'])
            ->where('status','publicado');

        // Buscar por "busca" (título/descrição)
        $busca = trim((string)$request->query('busca'));
        $query->when($busca !== '', function ($q) use ($busca) {
            $q->where(function($w) use ($busca){
                $w->where('titulo','like',"%{$busca}%")
                    ->orWhere('descricao_curta','like',"%{$busca}%")
                        ->orWhere('cursos.descricao_completa','like',"%{$busca}%");

            });
        });

        // Filtrar por categoria (id)
        $categoriaId = $request->query('categoria');
        $query->when($categoriaId, function($q) use ($categoriaId){
            $q->where('categoria_id', $categoriaId);
            // Se você quiser filtrar por nome em vez de id:
            // $q->whereHas('categoria', fn($c)=>$c->where('nome',$categoriaId));
        });

        $cursos = $query->paginate(12)->appends($request->query());
        $categorias = Categorias::orderBy('nome')->get();

        return view('site.catalogo', compact('cursos','categorias'));
    }


    public function show($id)
    {
        $curso = Cursos::with([
            'categoria',
            'instrutor',
            'modulos.aulas',
            //'avaliacoes.usuario'
        ])->findOrFail($id);

        $alunoId = auth('aluno')->id() ?? request()->session()->get('aluno_id');
        $matriculaVigente = $alunoId
            ? Matriculas::cicloVigente((int) $alunoId, (int) $curso->id)
            : null;

        return view('site.curso-detalhe', compact('curso', 'matriculaVigente'));

//        return response()->json($curso);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'categoria_id' => 'required|exists:categorias,id',
            'nivel' => 'required|in:Iniciante,Intermediário,Avançado',
            'preco' => 'nullable|numeric|min:0',
            'duracao_horas' => 'nullable|integer|min:1',
            'imagem_capa' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'resumo' => 'nullable|string',
        ]);

        if ($request->hasFile('imagem_capa')) {
            $validated['imagem_capa'] = $request->file('imagem_capa')
                ->store('cursos/capas', 'public');
        }

        $validated['instrutor_id'] = auth()->id();
        $validated['status'] = $request->is_draft ? 'rascunho' : 'publicado';

        $curso = Cursos::create($validated);

        // Criar módulos e aulas se fornecidos
        if ($request->modulos) {
            foreach ($request->modulos as $index => $moduloData) {
                $modulo = $curso->modulos()->create([
                    'titulo' => $moduloData['titulo'],
                    'descricao' => $moduloData['descricao'],
                    'ordem' => $index + 1
                ]);

                if (isset($moduloData['lessons'])) {
                    foreach ($moduloData['lessons'] as $lessonIndex => $aulaData) {
                        $modulo->aulas()->create([
                            'titulo' => $aulaData['titulo'],
                            'duracao_minutos' => $aulaData['duracao'],
                            'tipo_conteudo' => $aulaData['tipo'],
                            'ordem' => $lessonIndex + 1
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'curso' => $curso->load('modulos.aulas')
        ]);
    }
}
