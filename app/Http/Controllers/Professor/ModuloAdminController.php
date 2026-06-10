<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Cursos;
use App\Models\Modulos;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ModuloAdminController extends Controller
{
    public function index(Cursos $curso)
    {
        $this->authorizeCurso($curso);
        $modulos = $curso->modulos()->get();
        return view('prof.modulos.index', compact('curso','modulos'));
    }

    public function store(Request $request, Cursos $curso)
    {
        $this->authorizeCurso($curso);

        $data = $request->validate([
            'titulo'    => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'ordem'     => 'nullable|integer|min:0'
        ]);

        $data['curso_id'] = $curso->id;
        Modulos::create($data);

        return back()->with('success','Módulo criado!');
    }

    public function update(Request $request, Cursos $curso, Modulos $modulo)
    {
        $this->authorizeCurso($curso);
        $this->authorizeModulo($curso, $modulo);

        $data = $request->validate([
            'titulo'    => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'ordem'     => 'nullable|integer|min:0'
        ]);

        $modulo->update($data);

        return back()->with('success','Módulo atualizado!');
    }

    public function destroy(Cursos $curso, Modulos $modulo)
    {
        $this->authorizeCurso($curso);
        $this->authorizeModulo($curso, $modulo);

        $modulo->delete();
        return back()->with('success','Módulo removido.');
    }

    public function reorder(Request $request, Cursos $curso)
    {
        $this->authorizeCurso($curso);

        $data = $request->validate([
            'ordens' => 'required|array' // ex.: [['id'=>1,'ordem'=>1], ...]
        ]);

        foreach ($data['ordens'] as $it) {
            Modulos::where('id', $it['id'])->where('curso_id',$curso->id)->update(['ordem'=>$it['ordem']]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'modulos' => $curso->modulos()->get(['id', 'ordem']),
            ]);
        }

        return back()->with('success','Ordenação salva!');
    }

    public function copyToCourse(Request $request, Cursos $curso, Modulos $modulo)
    {
        $this->authorizeCurso($curso);
        $this->authorizeModulo($curso, $modulo);

        $data = $request->validate([
            'curso_destino_id' => ['required', 'exists:cursos,id'],
        ]);

        $destino = Cursos::where('professor_id', session('prof_id'))->findOrFail($data['curso_destino_id']);

        DB::transaction(function () use ($modulo, $destino) {
            $this->duplicateModuleIntoCourse($modulo, $destino);
        });

        return back()->with('success', 'Módulo copiado para o curso selecionado.');
    }

    public function importToCourse(Request $request, Cursos $curso)
    {
        $this->authorizeCurso($curso);

        $data = $request->validate([
            'modulo_origem_id' => ['required', 'exists:modulos,id'],
        ]);

        $modulo = Modulos::with('curso')
            ->whereHas('curso', fn($q) => $q->where('professor_id', session('prof_id')))
            ->findOrFail($data['modulo_origem_id']);

        DB::transaction(function () use ($modulo, $curso) {
            $this->duplicateModuleIntoCourse($modulo, $curso);
        });

        return back()->with('success', 'Módulo importado para este curso.');
    }

    private function duplicateModuleIntoCourse(Modulos $modulo, Cursos $destino): Modulos
    {
        $modulo->load(['aulas.materiais', 'quiz.questoes.opcoes']);

        $novaOrdem = ((int) $destino->modulos()->max('ordem')) + 1;
        $novoModulo = Modulos::create([
            'curso_id' => $destino->id,
            'titulo' => $modulo->titulo,
            'descricao' => $modulo->descricao,
            'ordem' => $novaOrdem,
        ]);

        foreach ($modulo->aulas as $aula) {
            $novaAula = $novoModulo->aulas()->create([
                'titulo' => $aula->titulo,
                'descricao' => $aula->descricao,
                'tipo' => $aula->tipo,
                'duracao_minutos' => $aula->duracao_minutos,
                'conteudo_url' => $aula->conteudo_url,
                'conteudo_texto' => $aula->conteudo_texto,
                'ordem' => $aula->ordem,
                'liberada_apos_anterior' => $aula->liberada_apos_anterior,
            ]);

            foreach ($aula->materiais as $material) {
                $novaAula->materiais()->create([
                    'nome_arquivo' => $material->nome_arquivo,
                    'tipo_arquivo' => $material->tipo_arquivo,
                    'url_download' => $material->url_download,
                    'tamanho_kb' => $material->tamanho_kb,
                ]);
            }
        }

        if ($modulo->quiz) {
            $novoQuiz = $novoModulo->quiz()->create([
                'curso_id' => $destino->id,
                'titulo' => $modulo->quiz->titulo,
                'descricao' => $modulo->quiz->descricao,
                'escopo' => 'modulo',
                'correcao_manual' => (bool) $modulo->quiz->correcao_manual,
            ]);

            foreach ($modulo->quiz->questoes as $questao) {
                $novaQuestao = $novoQuiz->questoes()->create([
                    'enunciado' => $questao->enunciado,
                    'tipo' => $questao->tipo,
                    'pontuacao' => $questao->pontuacao,
                    'ordem' => $questao->ordem,
                ]);

                foreach ($questao->opcoes as $opcao) {
                    $novaQuestao->opcoes()->create([
                        'texto' => $opcao->texto,
                        'correta' => (bool) $opcao->correta,
                    ]);
                }
            }
        }

        return $novoModulo;
    }

    private function authorizeCurso(Cursos $curso)
    {
        if ($curso->professor_id != session('prof_id')) abort(403);
    }

    private function authorizeModulo(Cursos $curso, Modulos $modulo)
    {
        if ($modulo->curso_id != $curso->id) abort(404);
    }
}
