<?php

namespace App\Services;

use App\Models\{Matriculas, ProgressoAula, Quiz, QuizTentativa, Certificados, Cursos};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CourseCompletionService
{
    /**
     * >>> REGRA DE CERTIFICADO (fonte única da verdade) <<<
     * SIMPLIFICADO (AGORA):
     *  - Certificado é elegível SOMENTE quando:
     *      a) TODAS as provas com escopo "modulo" do curso tiverem ao menos 1 tentativa
     *      b) A MÉDIA dessas provas (0–10) >= nota_minima_aprovacao (default 7.0)
     *
     * COMPLEXO (ANTES):
     *  - Misturava progresso de aula (% assistida / tempo) + provas + outros sinais.
     *  - Removemos 100% dessa influência: progresso de aula NÃO afeta certificado.
     */

    /**
     * Recalcula APENAS a elegibilidade do certificado (não mexe em % de aula).
     * Chamar após submissão/alteração de prova.
     */
    public function recalculateCertification(Matriculas $matricula): array
    {
        return DB::transaction(function () use ($matricula) {
            $elig = $this->checkEligibility($matricula);

            if ($elig['elegivel']) {
                // Marca a matrícula como concluída e emite/valida certificado
                $matricula->status = 'concluido';
                if (!$matricula->data_conclusao) {
                    $matricula->data_conclusao = now();
                }

                $cert = Certificados::where('matricula_id', $matricula->id)->first();
                if (!$cert) {
                    Certificados::create([
                        'matricula_id'       => $matricula->id,
                        'codigo_verificacao' => strtoupper(Str::random(10)),
                        'data_emissao'       => now(),
                        'valido'             => true,
                        'url_certificado'    => null,
                        'qr_code_url'        => null,
                    ]);
                } else {
                    // Se já existe, garante validade
                    if (property_exists($cert, 'valido')) {
                        $cert->valido = true;
                        $cert->save();
                    }
                }
            } else {
                // NÃO elegível: opcionalmente invalida certificado existente (mantém histórico)
                $cert = Certificados::where('matricula_id', $matricula->id)->first();
                if ($cert && property_exists($cert, 'valido')) {
                    $cert->valido = false;
                    $cert->save();
                }
            }

            // ATENÇÃO: não tocar em progresso_porcentagem aqui!
            // (ANTES: costumávamos recalcular % aqui; AGORA: removido)

            $matricula->nota_final = $elig['media']; // pode ser null se ainda sem tentativas
            $matricula->save();

            return $elig;
        });
    }

    /**
     * Calcula média e verifica elegibilidade.
     * - Considera quizzes do curso com escopo='modulo'
     * - Pega a ÚLTIMA tentativa do aluno em cada quiz
     * - Normaliza nota para 0–10 (usa nota_normalizada_0a10 se houver, senão pontuação)
     */
    public function checkEligibility(Matriculas $matricula): array
    {
        $curso = $matricula->curso()->firstOrFail();
        $notaMinima = (float) ($curso->nota_minima_aprovacao ?? 7.0);

        $quizIds = Quiz::where('curso_id', $curso->id)
            ->where('escopo', 'modulo')
            ->pluck('id');

        $total = $quizIds->count();
        if ($total === 0) {
            // Sem provas de módulo => por regra de negócio, NÃO libera certificado
            return ['elegivel' => false, 'media' => null, 'exigido' => $notaMinima, 'faltando' => 0];
        }

        $notas = [];
        foreach ($quizIds as $qid) {
            $t = QuizTentativa::where('quiz_id', $qid)
                ->where('matricula_id', $matricula->id)
                ->orderByDesc('id')
                ->first();

            if (!$t) continue; // sem tentativa para este quiz

            // Normalização de nota (0–10). As tentativas guardam pontos brutos.
            if (isset($t->nota_normalizada_0a10)) {
                $nota = (float) $t->nota_normalizada_0a10;
            } elseif (isset($t->nota_obtida, $t->nota_maxima) && (float) $t->nota_maxima > 0) {
                $nota = ((float) $t->nota_obtida / (float) $t->nota_maxima) * 10.0;
            } elseif (isset($t->nota_obtida)) {
                $nota = (float) $t->nota_obtida;
            } elseif (isset($t->pontuacao_obtida) && isset($t->pontuacao_max) && $t->pontuacao_max > 0) {
                $nota = (float) (($t->pontuacao_obtida / $t->pontuacao_max) * 10.0);
            } else {
                $nota = 0.0;
            }

            $notas[] = max(0, min(10, $nota));
        }

        $feitas   = count($notas);
        $faltando = $total - $feitas;
        $media    = $feitas > 0 ? round(array_sum($notas) / $feitas, 2) : null;
        $elegivel = ($faltando === 0) && ($media !== null) && ($media >= $notaMinima);

        return compact('elegivel', 'media') + ['exigido' => $notaMinima, 'faltando' => $faltando];
    }
}
