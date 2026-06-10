<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questoes', function (Blueprint $table) {
            $table->unsignedInteger('ordem')->default(0)->after('pontuacao');
            $table->index(['quiz_id', 'ordem'], 'quiz_questoes_quiz_ordem_idx');
        });

        $questoesPorQuiz = DB::table('quiz_questoes')
            ->select('id', 'quiz_id')
            ->orderBy('quiz_id')
            ->orderBy('id')
            ->get()
            ->groupBy('quiz_id');

        foreach ($questoesPorQuiz as $questoes) {
            $ordem = 1;
            foreach ($questoes as $questao) {
                DB::table('quiz_questoes')
                    ->where('id', $questao->id)
                    ->update(['ordem' => $ordem++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quiz_questoes', function (Blueprint $table) {
            $table->dropIndex('quiz_questoes_quiz_ordem_idx');
            $table->dropColumn('ordem');
        });
    }
};
