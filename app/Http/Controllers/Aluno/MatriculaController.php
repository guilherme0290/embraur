<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Cursos;
use App\Models\Matriculas;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    public function store(Request $request, Cursos $curso)
    {
        $alunoId = $request->session()->get('aluno_id');

        if ($matricula = Matriculas::cicloVigente((int) $alunoId, (int) $curso->id)) {
            $mensagem = $matricula->data_vencimento
                ? "Você já possui este curso válido até {$matricula->data_vencimento->format('d/m/Y')}."
                : 'Você já possui acesso ativo a este curso.';

            return redirect()->route('aluno.dashboard')->with('info', $mensagem);
        }

        Matriculas::criarNovoCiclo((int) $alunoId, $curso);

        return redirect()->route('aluno.dashboard')->with('success','Matrícula realizada com sucesso!');
    }
}
