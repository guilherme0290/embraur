<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->longText('conteudo_programatico')->nullable()->after('descricao_completa');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('conteudo_programatico');
        });
    }
};
