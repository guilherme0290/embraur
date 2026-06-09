<?php

namespace App\Http\Controllers;

use App\Models\AulaProgresso;
use App\Models\Aulas;
use App\Models\Matriculas;
use App\Models\ProgressoAula;
use App\Services\CourseCompletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AulaProgressoController extends Controller
{
    public function show(Request $rq, Aulas $aula)
    {
        $alunoId = auth('aluno')->id() ?? $rq->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        $cursoId = $aula->modulo->curso_id;
        $matricula = Matriculas::atualDoAlunoCurso(
            (int) $alunoId,
            (int) $cursoId,
            $rq->integer('matricula') ?: null
        );

        $p = ProgressoAula::where('matricula_id',$matricula->id)->where('aula_id',$aula->id)->first();

        return response()->json([
            'ok' => true,
            'progresso' => $p ? [
                'tempo_assistido_segundos' => (int)$p->tempo_assistido_segundos,
                'porcentagem_assistida'    => (float)$p->porcentagem_assistida,
                'concluida'                => (bool)$p->concluida,
            ] : null
        ]);
    }

//    public function store(Request $rq, Aulas $aula)
//    {
//
//        $alunoId = auth('aluno')->id() ?? $rq->session()->get('aluno_id');
//        abort_if(!$alunoId, 403);
//
//        $data = $rq->validate([
//            'segundos_assistidos' => 'required|integer|min:0',
//            'duracao_total' => 'nullable|integer|min:0', // opcional (MP4/player)
//            'marcar_concluida' => 'nullable|boolean',
//        ]);
//
//        $cursoId = $aula->modulo->curso_id;
//        $matricula = Matriculas::where('aluno_id', $alunoId)
//            ->where('curso_id', $cursoId)
//            ->firstOrFail();
//
//        $durTotal = (int)($data['duracao_total'] ?? ($aula->duracao_minutos * 60));
//        $durTotal = max(1, $durTotal); // evita divisão por zero
//        $pct = min(100, round(($data['segundos_assistidos'] / $durTotal) * 100, 2));
//        $concl = $pct >= 90 || $rq->boolean('marcar_concluida');
//
//// 1) cria se não existir e seta data_inicio apenas na criação
//        $p = ProgressoAula::firstOrCreate(
//            ['matricula_id' => $matricula->id, 'aula_id' => $aula->id],
//            ['data_inicio' => now()] // só será aplicado no INSERT
//        );
//
//// 2) atualiza o restante
//        $p->tempo_assistido_segundos = (int)$data['segundos_assistidos'];
//        $p->porcentagem_assistida = $pct;
//        $p->concluida = $concl;
//        $p->data_conclusao = $concl ? now() : null;
//        $p->save();
//
//// (opcional) recalcular curso/certificado
//        app(\App\Services\CourseCompletionService::class)->touchAndMaybeComplete($matricula);
//
//        return response()->json(['ok' => true, 'pct' => $pct, 'concluida' => $concl]);
//    }


// COMPLEXO (ANTES):
// app(\App\Services\CourseCompletionService::class)->touchAndMaybeComplete($matricula);

// SIMPLIFICADO (AGORA):
// Não chamar nada relacionado a certificado aqui.
// Apenas salve métricas da aula se quiser manter histórico.
// O certificado depende EXCLUSIVAMENTE das provas de módulo.

}
