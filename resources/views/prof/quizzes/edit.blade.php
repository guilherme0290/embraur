@extends('layouts.app')
@section('title','Editar Quiz')

@section('content')
    <div class="container-page mx-auto py-6 max-w-5xl">


        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('prof.quizzes.index', ['curso' => $quiz->curso_id]) }}" class="btn btn-outline">← Voltar</a>
            <div class="flex items-center gap-2">
                <form method="POST"
                      action="{{ route('prof.quizzes.destroy', $quiz->id) }}"
                      data-delete-quiz-form>
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

    <div id="deleteQuizModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 px-4"
         data-delete-quiz-modal
         aria-hidden="true">
        <div class="w-full max-w-md rounded-lg border bg-white shadow-xl">
            <div class="border-b px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Excluir prova do módulo?</h2>
            </div>
            <div class="px-5 py-4 text-sm leading-6 text-slate-600">
                Esta ação removerá a prova e todas as tentativas/respostas dos alunos vinculadas a ela. Essa exclusão não pode ser desfeita.
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50 px-5 py-4">
                <button type="button" class="btn btn-outline" data-delete-quiz-cancel>Cancelar</button>
                <button type="button" class="btn bg-red-600 text-white hover:bg-red-700" data-delete-quiz-confirm>Excluir prova</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-delete-quiz-form]');
            const modal = document.querySelector('[data-delete-quiz-modal]');
            const cancel = modal?.querySelector('[data-delete-quiz-cancel]');
            const confirmButton = modal?.querySelector('[data-delete-quiz-confirm]');
            let confirmed = false;

            function openModal(){
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                modal?.setAttribute('aria-hidden', 'false');
                cancel?.focus();
            }

            function closeModal(){
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                modal?.setAttribute('aria-hidden', 'true');
            }

            form?.addEventListener('submit', (event) => {
                if (confirmed) return;
                event.preventDefault();
                openModal();
            });

            cancel?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            confirmButton?.addEventListener('click', () => {
                confirmed = true;
                form?.submit();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal?.getAttribute('aria-hidden') === 'false') {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
