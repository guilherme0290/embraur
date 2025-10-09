<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Support\CursoGate;
use App\Models\{Certificados, Cursos, Matriculas, Modulos, Aulas, QuizTentativa};
use Illuminate\Http\Request;

class CursoConteudoController extends Controller
{
    public function show(Request $request, Cursos $curso, ?Modulos $modulo = null, ?Aulas $aula = null)
    {
        $alunoId = auth('aluno')->id() ?? $request->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        $matricula = Matriculas::where('aluno_id', $alunoId)
            ->where('curso_id', $curso->id)
            ->firstOrFail();

        // carrega relações usadas na página
        $curso->load(['modulos.aulas', 'modulos.quiz']);

        // 1) Usa os parâmetros, se vierem válidos
        if ($modulo && (int)$modulo->curso_id !== (int)$curso->id) {
            $modulo = null;
            $aula = null;
        }
        if ($modulo && $aula && (int)$aula->modulo_id !== (int)$modulo->id) {
            $aula = null;
        }

        // 2) Fallback: primeiro módulo/aula
        $moduloAtual = $modulo ?: $curso->modulos->sortBy('ordem')->values()->first();
        abort_if(!$moduloAtual, 404, 'Curso sem módulos.');

        $aulaAtual = $aula ?: $moduloAtual->aulas->sortBy('ordem')->values()->first();
        abort_if(!$aulaAtual, 404, 'Módulo sem aulas.');

        // Gate de acesso ao módulo
        $modIndex = $curso->modulos->sortBy('ordem')->values()
            ->search(fn($m) => (int)$m->id === (int)$moduloAtual->id);
        if (class_exists(CursoGate::class)) {
            $pode = CursoGate::podeAcessarModulo($curso, $matricula, $modIndex);
            abort_if(!$pode, 403, 'Módulo bloqueado até aprovação no anterior.');
        }

        // Navegação dentro do módulo
        $aulasOrdenadas = $moduloAtual->aulas->sortBy('ordem')->values();
        $idx = $aulasOrdenadas->search(fn($x) => (int)$x->id === (int)$aulaAtual->id);
        $prevAula = $idx > 0 ? $aulasOrdenadas[$idx - 1] : null;
        $nextAula = $idx < ($aulasOrdenadas->count() - 1) ? $aulasOrdenadas[$idx + 1] : null;

        // Ordena módulos e acha índice do atual
        $modsSorted = $curso->modulos->sortBy(fn($m) => [$m->ordem ?? 999999, $m->id])->values();
        $modIndex   = $modsSorted->search(fn($m) => (int)$m->id === (int)$moduloAtual->id);

        // >>> ADIÇÃO: próxima aula no próximo módulo (se não houver $nextAula e não houver quiz)
        $nextAulaCross = null;
        $proxModulo = $modsSorted[$modIndex + 1] ?? null;

        if (!$nextAula && $proxModulo) {
            // Respeita o gate para o próximo módulo, se existir
            $podeProx = true;
            if (class_exists(CursoGate::class)) {
                $podeProx = CursoGate::podeAcessarModulo($curso, $matricula, $modIndex + 1);
            }

            if ($podeProx) {
                $nextAulaCross = $proxModulo->aulas->sortBy('ordem')->values()->first();
            }
        }

        $modsSorted = $curso->modulos->sortBy(fn($m) => [$m->ordem ?? 999999, $m->id])->values();
        $modIndex   = $modsSorted->search(fn($m) => (int)$m->id === (int)$moduloAtual->id);
        // Status das provas por módulo
        $quizIds = $curso->modulos->pluck('quiz.id')->filter()->values();
        $ultimaTentativaPorQuiz = collect();
        if ($quizIds->isNotEmpty()) {
            $ultimaTentativaPorQuiz = QuizTentativa::where('matricula_id', $matricula->id)
                ->whereIn('quiz_id', $quizIds)
                ->orderByDesc('id')
                ->get()
                ->groupBy('quiz_id')
                ->map->first();
        }


        $certificado = $certificado ?? Certificados::where('matricula_id', $matricula->id)
            ->latest('data_emissao')->first();

        return view('aluno.curso-conteudo', [
            'curso'                  => $curso,
            'matricula'              => $matricula,
            'modulo'                 => $moduloAtual,
            'aula'                   => $aulaAtual,
            'prevAula'               => $prevAula,
            'nextAula'               => $nextAula,
            'nextAulaCross'          => $nextAulaCross,
            'modIndex'               => $modIndex,
            'ultimaTentativaPorQuiz' => $ultimaTentativaPorQuiz,
            'certificado'           => $certificado,
        ]);
    }
}
