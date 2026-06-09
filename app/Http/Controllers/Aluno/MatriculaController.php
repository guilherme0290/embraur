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

        if (!Matriculas::possuiCicloVigente((int) $alunoId, (int) $curso->id)) {
            Matriculas::criarNovoCiclo((int) $alunoId, $curso);
        }

        return redirect()->route('aluno.dashboard')->with('ok','Matrícula realizada com sucesso!');
    }
}
