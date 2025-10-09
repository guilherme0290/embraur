<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\{
    Quiz,
    QuizQuestao,
    QuizTentativa,
    QuizResposta,
    Matriculas,
    Cursos
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlunoQuizController extends Controller
{
    /**
     * Exibe a prova do módulo/curso para o aluno autenticado.
     */
    public function show(Request $rq, Cursos $curso, Quiz $quiz)
    {
        $alunoId = auth('aluno')->id() ?? $rq->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        $matricula = Matriculas::where('aluno_id',$alunoId)
            ->where('curso_id',$curso->id)->firstOrFail();

        $quiz->load('questoes.opcoes');
        // carrega conteúdo para a sidebar
        $curso->load(['modulos.aulas', 'modulos.quiz']);

        return view('aluno.quiz', compact('curso','quiz','matricula'));
    }


    /**
     * Corrige e salva a tentativa do aluno.
     * - Calcula nota por pontos e também a nota normalizada 0–10 (para comparação com a nota mínima do curso).
     * - Redireciona para a tela de resultado.
     */
    public function submit(Request $rq, Cursos $curso, Quiz $quiz)
    {
        $alunoId = auth('aluno')->id() ?? $rq->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        // Confere matrícula
        $matricula = Matriculas::where('aluno_id', $alunoId)
            ->where('curso_id', $curso->id)
            ->firstOrFail();

        // Payload: array de respostas [{questao_id, opcao_id? , resposta_texto?}]
        $payload = $rq->validate([
            'respostas'                     => 'required|array',
            'respostas.*.questao_id'        => 'required|integer|exists:quiz_questoes,id',
            'respostas.*.opcao_id'          => 'nullable|integer',
            'respostas.*.resposta_texto'    => 'nullable|string',
        ]);

        // Mapa de questões com opções
        $questoes = $quiz->questoes()->with('opcoes')->get()->keyBy('id');

        $notaPontos = 0.0;   // pontos obtidos
        $notaMaxima = 0.0;   // soma das pontuações das questões
        $tentativa  = null;  // para ficar acessível após a transaction

        DB::transaction(function () use (&$notaPontos, &$notaMaxima, &$tentativa, $quiz, $matricula, $payload, $questoes, $alunoId) {

            // cria a tentativa
            $tentativa = QuizTentativa::create([
                'quiz_id'      => $quiz->id,
                'aluno_id'     => $alunoId,
                'matricula_id' => $matricula->id,
            ]);

            // corrige questão a questão
            foreach ($questoes as $qid => $q) {
                $valorQuestao = (float) ($q->pontuacao ?? 1);
                $notaMaxima  += $valorQuestao;

                // procura a resposta enviada para esta questão
                $resp = collect($payload['respostas'])->firstWhere('questao_id', $qid);
                $pontosObtidos = 0.0;

                if ($q->tipo === 'multipla') {
                    $opCorreta = $q->opcoes->firstWhere('correta', true);
                    $escolhida = (int) ($resp['opcao_id'] ?? 0);

                    if ($opCorreta && $escolhida === (int) $opCorreta->id) {
                        $pontosObtidos = $valorQuestao;
                    }

                    QuizResposta::create([
                        'tentativa_id'      => $tentativa->id,
                        'questao_id'        => $qid,
                        'opcao_id'          => $resp['opcao_id'] ?? null,
                        'pontuacao_obtida'  => $pontosObtidos,
                    ]);
                } else {
                    // Questão discursiva: mantém 0 até correção manual
                    QuizResposta::create([
                        'tentativa_id'      => $tentativa->id,
                        'questao_id'        => $qid,
                        'resposta_texto'    => $resp['resposta_texto'] ?? null,
                        'pontuacao_obtida'  => 0,
                    ]);
                }

                $notaPontos += $pontosObtidos;
            }

            // Nota final normalizada de 0 a 10 (ex.: 7.5)
            $nota10 = $notaMaxima > 0 ? round(($notaPontos / $notaMaxima) * 10, 1) : 0.0;

            // Aprovado se nota10 >= nota mínima do curso
            $notaMinima = (float) ($quiz->curso->nota_minima_aprovacao ?? 0);
            $aprovado   = $notaMaxima > 0 && $nota10 >= $notaMinima;

            // persiste totais e status
            $tentativa->update([
                'nota_obtida'   => $notaPontos,     // pontos brutos
                'nota_maxima'   => $notaMaxima,     // pontos possíveis
                'aprovado'      => $aprovado,
                'concluido_em'  => Carbon::now(),
            ]);
        });

        // Dentro do método submit() DEPOIS de salvar a tentativa ($tent)
        app(\App\Services\CourseCompletionService::class)->recalculateCertification($matricula);


        // redireciona para a tela de resultado
        return redirect()
            ->route('aluno.quiz.result', [$curso->id, $quiz->id, $tentativa->id]);
    }

    /**
     * Tela final com feedback: nota, aprovado/reprovado e resumo de respostas.
     */
    public function result(Request $rq, Cursos $curso, Quiz $quiz, QuizTentativa $tentativa)
    {
        $alunoId = auth('aluno')->id() ?? $rq->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        // tentativa precisa pertencer ao aluno e ao quiz
        abort_if($tentativa->aluno_id !== $alunoId || $tentativa->quiz_id !== $quiz->id, 403);

        // carrega questões + opções
        $quiz->load('questoes.opcoes');

        // respostas desta tentativa
        $respostas = QuizResposta::where('tentativa_id', $tentativa->id)->get()->keyBy('questao_id');

        // notas em 0–10 para comparação/exibição
        $notaMinima = (float) ($curso->nota_minima_aprovacao ?? 0);
        $nota10     = ($tentativa->nota_maxima > 0)
            ? round(($tentativa->nota_obtida / $tentativa->nota_maxima) * 10, 1)
            : 0.0;

        $aprovado = $nota10 >= $notaMinima;

        // monta resumo p/ a lista
        $resumo = [];
        foreach ($quiz->questoes as $q) {
            $resp    = $respostas->get($q->id);
            $opSua   = $q->opcoes->firstWhere('id', $resp->opcao_id ?? 0);
            $opCor   = $q->opcoes->firstWhere('correta', true);

            $resumo[] = [
                'questao'  => $q,
                'sua'      => $opSua,
                'correta'  => $opCor,
                'ok'       => $opSua && $opCor && ((int)$opSua->id === (int)$opCor->id),
                'pontos'   => (float)($q->pontuacao ?? 1),
            ];
        }

        /**
         * 🔓 Desbloqueio do próximo módulo
         * O seu gate (CursoGate::podeAcessarModulo) olha se o quiz do módulo ANTERIOR foi aprovado.
         * Como agora existe uma tentativa aprovada registrada (quiz_tentativas.aprovado = 1),
         * o próximo módulo ficará automaticamente liberado – não é preciso gravar nada extra.
         */

        $curso->load([
            'modulos.aulas' => fn($q) => $q->orderByRaw('COALESCE(ordem, 999999), id')
        ]);

        $modsSorted = $curso->modulos
            ->sortBy(fn($m) => [$m->ordem ?? 999999, $m->id])
            ->values();

        // índice do módulo atual DENTRO da coleção (não usar 'ordem' como índice!)
        $idxAtual = $modsSorted->search(fn($m) => (int)$m->id === (int)optional($quiz->modulo)->id);

        // próximo módulo (se existir)
        $proximoModulo = ($idxAtual !== false) ? ($modsSorted[$idxAtual + 1] ?? null) : null;

        // primeira aula do próximo módulo (pula módulos sem aulas, se houver)
        $primeiraAulaProx = null;
        if ($proximoModulo) {
            $aulas = $proximoModulo->aulas
                ->sortBy(fn($a) => [$a->ordem ?? 999999, $a->id])
                ->values();
            $primeiraAulaProx = $aulas->first();
        }

        return view('aluno.quiz-resultado', [
            'curso'      => $curso,
            'quiz'       => $quiz,
            'tentativa'  => $tentativa,
            'notaMinima' => $notaMinima,
            'nota10'     => $nota10,
            'aprovado'   => $aprovado,
            'resumo'     => $resumo,
            'respostas'  => $respostas,
            'proximoModulo'     => $proximoModulo,
            'primeiraAulaProx'  => $primeiraAulaProx,
        ]);
    }

    public function refazer(Request $rq, Cursos $curso, Quiz $quiz)
    {
        $alunoId = auth('aluno')->id() ?? $rq->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        return redirect()->route('aluno.quiz.show', [$curso->id, $quiz->id]);
    }


}
