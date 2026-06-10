<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Cursos;
use App\Models\Matriculas;
use App\Models\User;
use Illuminate\Http\Request;

class StudentCoursesController extends Controller
{
    public function index(Request $request)
    {
        $alunoId = auth('aluno')->id() ?? $request->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        $aluno = User::where('id', $alunoId)->where('tipo_usuario', 'aluno')->firstOrFail();

        $rows = Matriculas::with(['curso' => fn($q) => $q->withCount('aulas as aulas_total')])
            ->where('aluno_id', $alunoId)
            ->orderByDesc('data_matricula')
            ->orderByDesc('id')
            ->get();

        $cursos = $rows->map(function (Matriculas $matricula) {
            $curso = $matricula->curso;
            $progresso = (int) ($matricula->progresso_porcentagem ?? 0);
            $total = (int) ($curso->aulas_total ?? 0);
            $feitas = $total > 0 ? (int) round($progresso * $total / 100) : 0;

            return [
                'titulo'        => $curso->titulo ?? 'Curso',
                'ciclo'         => (int) ($matricula->ciclo_numero ?? 1),
                'status'        => $matricula->status_exibicao,
                'data_inicio'   => optional($matricula->data_inicio ?? $matricula->data_matricula)->format('d/m/Y'),
                'data_vencimento' => optional($matricula->data_vencimento)->format('d/m/Y'),
                'progresso'     => $progresso,
                'aulas_feitas'  => $feitas,
                'aulas_total'   => $total,
                'link'          => $curso ? route('aluno.curso.conteudo', [$curso->id, 'matricula' => $matricula->id]) : route('aluno.cursos'),
                '_model'        => $curso,
                '_matricula'    => $matricula,
            ];
        });

        return view('aluno.cursos', compact('aluno','cursos'));
    }
}
