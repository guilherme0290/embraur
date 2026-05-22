@extends('layouts.app')
@section('title','Prova do Módulo')

@section('content')
    <div class="container-page mx-auto py-6 max-w-5xl">


        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('prof.cursos.edit', $curso->id) }}" class="btn btn-outline">← Voltar ao Curso</a>
        </div>

        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h1 class="text-lg font-semibold mb-4">
                Criar Prova — {{ $curso->titulo }} / Módulo: {{ $modulo->titulo }}
            </h1>

            <form method="POST" action="{{ route('prof.quizzes.store') }}">
                @csrf

                {{-- Fixos para este fluxo --}}
                <input type="hidden" name="escopo" value="modulo">
                <input type="hidden" name="curso_id" value="{{ $curso->id }}">
                <input type="hidden" name="modulo_id" value="{{ $modulo->id }}">

                {{-- QUESTÕES (use o mesmo _form de questões que já sugeri) --}}
                @include('prof.quizzes._questoes')

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('prof.cursos.edit', $curso->id) }}" class="btn btn-outline">Cancelar</a>
                    <button class="btn btn-primary">Criar Prova</button>
                </div>
            </form>
        </div>
    </div>
@endsection
