@extends('layouts.app')
@section('title','Editar Quiz')

@section('content')
    <div class="container-page mx-auto py-6 max-w-5xl">


        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('prof.quizzes.index', ['curso' => $quiz->curso_id]) }}" class="btn btn-outline">← Voltar</a>
            <div class="flex items-center gap-2">
                <form method="POST"
                      action="{{ route('prof.quizzes.destroy', $quiz->id) }}"
                      onsubmit="return confirm('Excluir esta prova? As tentativas e respostas vinculadas também serão removidas.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline text-red-700">Excluir prova</button>
                </form>
                <a href="{{ route('prof.cursos.edit', $quiz->curso_id) }}" class="btn btn-outline">← Voltar para o curso</a>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h1 class="text-lg font-semibold mb-4">Editar Quiz</h1>
            <form method="POST" action="{{ route('prof.quizzes.update', $quiz->id) }}">
                @csrf
                @method('PUT')
                @include('prof.quizzes._form', [
                    'quiz' => $quiz,
                    'cursos' => $cursos,
                    'modulosPorCurso' => $modulosPorCurso,
                ])
            </form>
        </div>
    </div>
@endsection
