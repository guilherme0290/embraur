{{-- resources/views/aluno/quiz-resultado.blade.php --}}
@extends('layouts.app')
@section('title', 'Resultado da Prova')

@section('content')
    @php
        $aprovado = (bool) $tentativa->aprovado;
        $matricula = $tentativa->matricula;
        $notaFmt  = number_format($nota10, 1, ',', '');
        $minFmt   = number_format((float)$notaMinima, 1, ',', '');

        // Descobrir próximo módulo (para botão "Continuar curso")
        $modsOrdenados = $curso->modulos->sortBy('ordem')->values();
        $idxAtual = optional($modsOrdenados->firstWhere('id', optional($quiz->modulo)->id))->ordem ?? null;

        // se não tiver 'ordem' no banco, localizar pelo índice na coleção
        if ($idxAtual === null) {
            $idxAtual = $modsOrdenados->search(fn($m) => (int)$m->id === (int)optional($quiz->modulo)->id);
        }
        $proximoModulo = $idxAtual !== null ? $modsOrdenados->get($idxAtual + 1) : null;

        // últimas tentativas por quiz (pintar badges no sidebar)
        $quizIds = $curso->modulos->pluck('quiz.id')->filter()->values();
        $ultimas = collect();
        if ($quizIds->isNotEmpty()) {
            $ultimas = \App\Models\QuizTentativa::where('matricula_id', $matricula->id)
                ->whereIn('quiz_id', $quizIds)
                ->orderByDesc('id')
                ->get()
                ->groupBy('quiz_id')
                ->map->first();
        }
    @endphp

    <div class="container mx-auto py-6">
        {{-- trilha/topbar --}}
        <div class="mb-4 flex items-center justify-between text-sm">
            <div class="text-slate-600 flex items-center gap-2">
                <a href="{{ route('aluno.dashboard') }}" class="hover:underline">&larr; Voltar ao Dashboard</a>
                <span class="text-slate-400">/</span>
                <a href="{{ route('aluno.curso.conteudo', [$curso->id, 'matricula' => $matricula->id]) }}" class="hover:underline">{{ $curso->titulo }}</a>
            </div>

            <a href="{{ route('aluno.curso.conteudo', [$curso->id, 'matricula' => $matricula->id]) }}"
               class="text-slate-600 hover:underline">← Voltar ao curso</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- COLUNA PRINCIPAL --}}
            <div class="lg:col-span-8 space-y-6">
                {{-- CARD DE STATUS/NOTA --}}
                <div class="bg-white border rounded-xl p-6">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-14 h-14 mb-3 rounded-full flex items-center justify-center
                        {{ $aprovado ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            {!! $aprovado ? '🔓' : '⚠️' !!}
                        </div>

                        <h2 class="text-lg font-semibold text-slate-900 mb-1">
                            Prova do {{ $quiz->modulo->titulo ?? 'Módulo' }} - Finalizada
                        </h2>

                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-slate-600">Nota obtida:</span>
                            <span class="inline-flex items-center gap-1 text-white bg-slate-900 px-2.5 py-1 rounded-full text-sm">
                            <strong>{{ $notaFmt }}</strong>
                            <span class="opacity-80">/ 10,0</span>
                        </span>
                        </div>

                        <p class="text-sm text-slate-500">Nota mínima para aprovação: {{ $minFmt }}</p>

                        @if($aprovado)
                            <p class="text-emerald-600 font-medium mt-3">
                                Parabéns! Você foi aprovado neste módulo.
                            </p>

                            <div class="mt-4">
                                @if(!empty($proximoModulo) && !empty($primeiraAulaProx))
                                    {{-- Leva direto para a 1ª aula do próximo módulo --}}
                                    <a href="{{ route('aluno.curso.modulo.aula', [$curso->id, $proximoModulo->id, $primeiraAulaProx->id, 'matricula' => $matricula->id]) }}"
                                       class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                        Continuar curso
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/>
                                        </svg>
                                    </a>
                                @else
                                    {{-- Se não há próximo módulo/aula, volta para o sumário do curso --}}
                                    <a href="{{ route('aluno.curso.conteudo', [$curso->id, 'matricula' => $matricula->id]) }}"
                                       class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                        Voltar ao curso
                                    </a>
                                @endif
                            </div>
                        @else
                            <p class="text-rose-600 font-medium mt-3">
                                Você não atingiu a nota mínima. Revise o conteúdo e tente novamente.
                            </p>
                            <div class="mt-4 flex items-center gap-3">
                                <a href="{{ route('aluno.curso.conteudo', [$curso->id, 'matricula' => $matricula->id]) }}"
                                   class="px-4 py-2 rounded border text-slate-700 hover:bg-slate-100">
                                    Revisar conteúdo
                                </a>
                                <a href="{{ route('aluno.quiz.refazer', [$curso->id, $quiz->id, 'matricula' => $matricula->id]) }}"
                                   class="px-4 py-2 rounded text-white bg-slate-700 hover:bg-slate-800">
                                    Refazer prova
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RESUMO DAS RESPOSTAS --}}
                <div class="bg-white border rounded-xl">
                    <div class="px-5 py-4 border-b">
                        <h3 class="font-semibold text-slate-800">Resumo das respostas:</h3>
                    </div>

                    <div class="divide-y">
                        @foreach($resumo as $i => $r)
                            @php
                                $ok   = (bool) ($r['ok'] ?? false);
                                $pnts = (float) ($r['questao']->pontuacao ?? 1);
                                $suaDisc = optional($respostas->get($r['questao']->id))->resposta_texto;
                                $sua = $r['sua']->texto ?? ($suaDisc ?: 'Não respondida');
                                $cor = $r['correta']->texto ?? '-';
                            @endphp
                            <div class="p-4 {{ $ok ? 'bg-emerald-50/40' : 'bg-rose-50/40' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-slate-800 font-medium">
                                            Questão {{ $i+1 }}: {!! $r['questao']->enunciado !!}
                                        </p>
                                        <p class="text-slate-600 text-sm mt-1">
                                            <strong>Sua resposta:</strong> <span class="{{ $ok ? 'text-emerald-700' : 'text-rose-700' }}">{{ $sua }}</span>
                                        </p>
                                        <p class="text-slate-500 text-sm">
                                            <strong>Resposta correta:</strong> {{ $cor }}
                                        </p>
                                    </div>

                                    {{-- badge de pontos da questão --}}
                                    <span class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-full text-sm
                                    {{ $ok ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $ok ? '+'.$pnts : '0' }}
                                </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SIDEBAR – CONTEÚDO DO CURSO (igual ao player) --}}
            <aside class="lg:col-span-4">
                <div class="bg-white border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-slate-800">Conteúdo do Curso</h4>
                        <span class="text-xs text-slate-500">0%</span>
                    </div>

                    @foreach($curso->modulos->sortBy('ordem') as $idx => $m)
                        @php
                            $qz     = $m->quiz ?? null;
                            $ult    = $qz ? ($ultimas->get($qz->id) ?? null) : null;
                            $badge  = $ult ? ($ult->aprovado ? 'OK' : 'Reprovado') : 'Pend.';
                            $isAtualModulo = (int)$m->id === (int)optional($quiz->modulo)->id;
                        @endphp

                        <div class="mt-4 first:mt-0">
                            <p class="text-slate-700 font-medium mb-2">
                                {{ $m->titulo }}
                                @if($isAtualModulo)
                                    <span class="ml-2 text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full">Atual</span>
                                @endif
                            </p>

                            <ul class="space-y-1">
                                @foreach($m->aulas as $aula)
                                    <li class="flex items-center gap-2 text-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                        <span class="text-slate-600">{{ $aula->titulo }}</span>
                                    </li>
                                @endforeach

                                @if($qz)
                                    <li class="flex items-center gap-2 text-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                        <a href="{{ route('aluno.quiz.show', [$curso->id, $qz->id, 'matricula' => $matricula->id]) }}"
                                           class="text-slate-800 font-medium hover:underline">Prova do {{ $m->titulo }}</a>
                                        <span class="ml-auto text-xs
                                        @if($badge==='OK') text-green-700 @elseif($badge==='Reprovado') text-rose-700 @else text-slate-500 @endif">
                                        {{ $badge }}
                                    </span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
@endsection
