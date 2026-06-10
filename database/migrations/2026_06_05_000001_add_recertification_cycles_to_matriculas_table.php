<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->index('aluno_id', 'matriculas_aluno_id_idx');
            $table->index('curso_id', 'matriculas_curso_id_idx');
        });

        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropUnique('matriculas_aluno_id_curso_id_unique');
            $table->unsignedInteger('ciclo_numero')->default(1)->after('curso_id');
            $table->unsignedBigInteger('recertificacao_de_matricula_id')->nullable()->after('ciclo_numero');
            $table->index(['aluno_id', 'curso_id', 'ciclo_numero'], 'matriculas_aluno_curso_ciclo_idx');
            $table->foreign('recertificacao_de_matricula_id', 'matriculas_recertificacao_fk')
                ->references('id')
                ->on('matriculas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::statement('DELETE m1 FROM matriculas m1 INNER JOIN matriculas m2 ON m1.aluno_id = m2.aluno_id AND m1.curso_id = m2.curso_id AND m1.id < m2.id');

        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropForeign('matriculas_recertificacao_fk');
            $table->dropIndex('matriculas_aluno_curso_ciclo_idx');
            $table->dropColumn(['ciclo_numero', 'recertificacao_de_matricula_id']);
            $table->unique(['aluno_id', 'curso_id']);
        });

        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropIndex('matriculas_aluno_id_idx');
            $table->dropIndex('matriculas_curso_id_idx');
        });
    }
};
