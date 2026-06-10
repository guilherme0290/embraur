<?php

namespace App\Support;

use App\Models\Cursos;
use App\Models\Matriculas;
use App\Models\Quiz;
use App\Models\QuizTentativa;
use Illuminate\Support\Facades\Schema;

class CursoGate
{
    // retorna true se aluno pode acessar $moduloIndex (0-based)
    public static function podeAcessarModulo(Cursos $curso, Matriculas $matricula, int $moduloIndex): bool
    {
        // Primeiro módulo é sempre liberado
        if ($moduloIndex <= 0) {
            return true;
        }

        // Nota mínima do curso (default 7.0)
        $notaMin = (float) ($curso->nota_minima_aprovacao ?? 7.0);

        // Mesma ordenação em TODOS os lugares: ordem ASC, id ASC como desempate
        $modulos = $curso->modulos()
            ->orderByRaw('COALESCE(ordem, 999999), id')
            ->get()
            ->values();

        $prev = $modulos[$moduloIndex - 1] ?? null;
        if (!$prev) {
            return true;
        }

        // Quizzes do módulo anterior, apenas do escopo 'modulo' e (se houver) ativos
        $q = Quiz::where('modulo_id', $prev->id)
            ->where('escopo', 'modulo');

        if (Schema::hasColumn('quizzes', 'ativo')) {
            $q->where('ativo', 1);
        }

        $quizIds = $q->pluck('id');

        // Se não há prova de módulo anterior, não bloqueia
        if ($quizIds->isEmpty()) {
            return true;
        }

        // Para CADA quiz exigido, o aluno precisa ter tentativa com nota >= notaMin
        foreach ($quizIds as $qid) {
            $tent = QuizTentativa::where('quiz_id', $qid)
                ->where('matricula_id', $matricula->id)
                ->orderByDesc('id')
                ->first();

            if (!$tent) {
                return false; // sem tentativa => bloqueado
            }

            $nota = self::nota0a10($tent);
            if ($nota < $notaMin) {
                return false; // reprovado => bloqueado
            }
        }

        return true; // passou em todas as provas de módulo anteriores
    }

    private static function nota0a10($t): float
    {
        if (isset($t->nota_normalizada_0a10)) {
            return max(0, min(10, (float)$t->nota_normalizada_0a10));
        }
        if (isset($t->nota_obtida, $t->nota_maxima) && (float) $t->nota_maxima > 0) {
            return max(0, min(10, ((float) $t->nota_obtida / (float) $t->nota_maxima) * 10.0));
        }
        if (isset($t->pontuacao_max) && $t->pontuacao_max > 0) {
            return max(0, min(10, (float)(($t->pontuacao_obtida ?? 0) / $t->pontuacao_max) * 10.0));
        }
        if (isset($t->nota_obtida)) {
            return max(0, min(10, (float)$t->nota_obtida));
        }
        return 0.0;
    }
}
