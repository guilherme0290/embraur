{{-- resources/views/aluno/quiz.blade.php --}}
@extends('layouts.app')
@section('title', $quiz->titulo)

@section('content')
    <style>
        /* 1 questão por vez */
        .quiz-question{display:none}
        .quiz-question.active{display:block}

        /* Sidebar fixa */
        .sticky-col{position:sticky; top:96px}
    </style>

    <div class="container mx-auto py-6">

        {{-- HEADER --}}
        <div class="rounded-lg border p-4 mb-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold">{{ $quiz->titulo }}</h1>

                    {{-- Subtítulo (usa descrição do quiz; se não tiver, usa nome do curso) --}}
                    <p class="text-sm text-slate-600">
                        {{ $quiz->descricao ?? ('Avaliação dos conceitos de ' . ($curso->titulo ?? '')) }}
                    </p>

                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-medium">
                            Nota mínima: {{ number_format((float)($curso->nota_minima_aprovacao ?? 7),1,',','.') }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-medium">
                            <span id="respondidasLabel">0 de {{ $quiz->questoes->count() }} respondidas</span>
                        </span>
                    </div>
                </div>

                <a href="{{ route('aluno.curso.conteudo', $curso->id) }}"
                   class="text-sm text-slate-600 hover:underline flex items-center gap-2 shrink-0">
                    <span class="-ml-1">←</span> Voltar às aulas
                </a>
            </div>

            {{-- Progresso + percentual --}}
            <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                <span>Progresso do quiz</span>
                <span id="pctLabel">0%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-200 mt-2 overflow-hidden">
                <div id="barraProgresso" class="h-full bg-blue-600 transition-all" style="width:0%"></div>
            </div>
        </div>

        <form method="POST" action="{{ route('aluno.quiz.submit', [$curso->id, $quiz->id]) }}" id="formQuiz">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

                {{-- COLUNA ESQUERDA (conteúdo do quiz) --}}
                <div class="md:col-span-8 lg:col-span-9">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

                        {{-- Navegação (pílulas) – visível no mobile/tablet --}}
                        <div class="md:col-span-4 lg:hidden">
                            <div class="rounded-lg border p-4">
                                <div class="font-semibold mb-3">Navegação</div>
                                <div class="grid grid-cols-6 sm:grid-cols-8 gap-2">
                                    @foreach($quiz->questoes as $i => $q)
                                        <button type="button"
                                                class="pill w-9 h-9 rounded border text-sm flex items-center justify-center hover:bg-slate-50"
                                                data-go="{{ $i }}">{{ $i+1 }}</button>
                                    @endforeach
                                </div>
                                <div class="mt-4 space-y-1 text-xs text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-slate-300 inline-block"></span> Não respondida
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Respondida
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-green-600 inline-block"></span> Atual
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Área de questão (1 por vez) --}}
                        <div class="md:col-span-8 lg:col-span-12">
                            @foreach($quiz->questoes as $k => $q)
                                <div class="quiz-question rounded-lg border p-4 mb-4 {{ $k===0 ? 'active' : '' }}" data-index="{{ $k }}">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold">Questão {{ $k+1 }} de {{ $quiz->questoes->count() }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ rtrim(rtrim(number_format((float)$q->pontuacao,1,',','.'),'0'),',') }} pontos
                                        </div>
                                    </div>

                                    <p class="mt-2">{!! $q->enunciado !!} </p>
                                    <input type="hidden" name="respostas[{{ $k }}][questao_id]" value="{{ $q->id }}"/>

                                    @if($q->tipo === 'multipla')
                                        <div class="mt-3 space-y-2">
                                            @foreach($q->opcoes as $op)
                                                <label class="flex items-center gap-2 rounded border p-3 hover:bg-slate-50 cursor-pointer">
                                                    <input type="radio"
                                                           name="respostas[{{ $k }}][opcao_id]"
                                                           value="{{ $op->id }}"
                                                           class="answer-radio mt-0.5"
                                                           data-q="{{ $k }}">
                                                    <span>{{ $op->texto }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <textarea name="respostas[{{ $k }}][resposta_texto]"
                                                  class="answer-text w-full mt-3 rounded border p-3"
                                                  rows="4" data-q="{{ $k }}"
                                                  placeholder="Escreva sua resposta..."></textarea>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Rodapé de navegação --}}
                            <div class="rounded-lg border p-4 mt-4 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btnPrev"
                                            class="px-3 py-2 border rounded hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        ← Anterior
                                    </button>
                                    <button type="button" id="btnNext"
                                            class="px-3 py-2 border rounded hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Próxima →
                                    </button>
                                </div>

                                <div class="text-sm text-slate-500">
                                    Questão <span id="lblAtual">1</span> de <span id="lblTotal">{{ $quiz->questoes->count() }}</span>
                                </div>

                                <button type="submit" id="btnSubmit"
                                        class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        style="display:none">
                                    Enviar Prova
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COLUNA DIREITA (conteúdo do curso – igual ao player) --}}
                <div class="md:col-span-4 lg:col-span-3">
                    <div class="sticky-col">
                        <div class="rounded-lg border p-4">
                            <h3 class="font-semibold mb-3">Conteúdo do Curso</h3>

                            @php
                                // Última tentativa por quiz (p/ pintar badge OK/Reprovado/Pend.)
                                $quizIds = $curso->modulos->pluck('quiz.id')->filter()->values();
                                $ultimas = collect();
                                if ($quizIds->isNotEmpty()) {
                                    $ultimas = \App\Models\QuizTentativa::where('aluno_id', $matricula->aluno_id)
                                        ->whereIn('quiz_id', $quizIds)
                                        ->orderByDesc('id')
                                        ->get()
                                        ->groupBy('quiz_id')
                                        ->map->first();
                                }

                                // Módulo atual = módulo deste quiz
                                $moduloAtualId = $quiz->modulo_id ?? optional($quiz->modulo)->id;
                            @endphp

                            @foreach($curso->modulos->sortBy('ordem') as $idx => $m)
                                @php
                                    $isAtual  = (int)$m->id === (int)$moduloAtualId;
                                    $qz       = $m->quiz ?? null;
                                    $tent     = $qz ? ($ultimas->get($qz->id) ?? null) : null;
                                    $status   = $tent ? ($tent->aprovado ? 'ok' : 'reprov') : 'pend';

                                    // trava visual (se você usa gate de módulos)
                                    $modLiberado = true;
                                    if (class_exists(\App\Support\CursoGate::class)) {
                                        $modLiberado = \App\Support\CursoGate::podeAcessarModulo($curso, $matricula, $idx);
                                    }
                                @endphp

                                <div class="mb-4">
                                    <div class="flex items-center justify-between">
                                        <div class="font-medium">
                                            Módulo {{ $idx+1 }} — {{ $m->titulo }}
                                        </div>
                                        @if(!$modLiberado)
                                            <span class="text-[11px] text-amber-700">Bloqueado</span>
                                        @elseif($isAtual)
                                            <span class="text-[11px] text-blue-600">Atual</span>
                                        @endif
                                    </div>

                                    @if($m->descricao)
                                        <div class="text-xs text-slate-500">{{ $m->descricao }}</div>
                                    @endif

                                    <div class="mt-2">
                                        @foreach($m->aulas->sortBy('ordem') as $a)
                                            <a href="{{ route('aluno.curso.modulo.aula', [$curso->id, $m->id, $a->id]) }}"
                                               class="flex items-center justify-between rounded border p-2 mb-1
                                                      {{ !$modLiberado ? 'opacity-60 pointer-events-none' : '' }} hover:bg-slate-50">
                                                <span class="truncate text-sm">{{ $a->titulo }}</span>
                                            </a>
                                        @endforeach

                                        {{-- Prova do módulo --}}
                                        <div class="flex items-center justify-between mt-2">
                                            @if($qz)
                                                <a href="{{ route('aluno.quiz.show', [$curso->id, $qz->id]) }}"
                                                   class="px-2 py-1 text-sm border rounded hover:bg-slate-50
                                                          {{ !$modLiberado ? 'opacity-60 pointer-events-none' : '' }}">
                                                    Prova do Módulo
                                                </a>
                                                @switch($status)
                                                    @case('ok')
                                                        <span class="text-[11px] px-2 py-1 rounded bg-green-100 text-green-700">OK</span>
                                                        @break
                                                    @case('reprov')
                                                        <span class="text-[11px] px-2 py-1 rounded bg-red-100 text-red-700">Reprovado</span>
                                                        @break
                                                    @default
                                                        <span class="text-[11px] px-2 py-1 rounded bg-slate-100 text-slate-600">Pend.</span>
                                                @endswitch
                                            @else
                                                <span class="text-xs text-slate-500">Prova do módulo não cadastrada</span>
                                            @endif
                                        </div>

                                        {{-- Aviso de bloqueio do próximo módulo --}}
                                        @if(!$modLiberado)
                                            <div class="mt-2 text-[11px] text-amber-700">
                                                Para acessar este módulo, conclua e seja aprovado na prova do módulo anterior.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    {{-- JS da navegação/estado do quiz --}}
    <script>
        (function(){
            const total = {{ $quiz->questoes->count() }};
            let page = 0;
            const answered = Array(total).fill(false);

            const questions = Array.from(document.querySelectorAll('.quiz-question'));
            const pills = Array.from(document.querySelectorAll('.pill'));
            const btnPrev = document.getElementById('btnPrev');
            const btnNext = document.getElementById('btnNext');
            const btnSubmit = document.getElementById('btnSubmit');
            const lblAtual = document.getElementById('lblAtual');
            const barra = document.getElementById('barraProgresso');
            const lblResp = document.getElementById('respondidasLabel');
            const pctLabel = document.getElementById('pctLabel');

            function setPillState(){
                pills.forEach((p,i)=>{
                    p.classList.remove('bg-blue-600','text-white','border-blue-600','bg-green-500','border-green-500');
                    if(i === page){
                        p.classList.add('bg-blue-600','text-white','border-blue-600');
                    } else if(answered[i]){
                        p.classList.add('bg-green-500','text-white','border-green-500');
                    }
                });
            }

            function showPage(i){
                questions.forEach((el, idx)=> el.classList.toggle('active', idx===i));
                page = i;
                lblAtual.textContent = (page+1);
                setPillState();
                updateButtons();
                questions[i].scrollIntoView({behavior:'smooth', block:'start'});
            }

            function updateButtons(){
                btnPrev.disabled = (page===0);
                if(page < total-1){
                    btnNext.style.display = '';
                    btnNext.disabled = !answered[page];
                    btnSubmit.style.display = 'none';
                } else {
                    btnNext.style.display = 'none';
                    btnSubmit.style.display = '';
                    btnSubmit.disabled = !answered.every(Boolean);
                }
            }

            function updateProgress(){
                const count = answered.filter(Boolean).length;
                const pct = Math.round((count/total)*100);
                barra.style.width = pct+'%';
                pctLabel.textContent = pct+'%';
                lblResp.textContent = `${count} de ${total} respondidas`;
                setPillState();
                updateButtons();
            }

            // marcações
            document.querySelectorAll('.answer-radio').forEach(r=>{
                r.addEventListener('change', e=>{
                    const k = +e.target.dataset.q;
                    if(!answered[k]){ answered[k] = true; updateProgress(); }
                });
            });

            document.querySelectorAll('.answer-text').forEach(t=>{
                t.addEventListener('input', e=>{
                    const k = +e.target.dataset.q;
                    const has = e.target.value.trim().length>0;
                    if(has !== answered[k]){ answered[k] = has; updateProgress(); }
                });
            });

            // navegação
            pills.forEach((p,i)=> p.addEventListener('click', ()=> showPage(i)));
            btnPrev.addEventListener('click', ()=> { if(page>0) showPage(page-1); });
            btnNext.addEventListener('click', ()=> { if(answered[page] && page<total-1) showPage(page+1); });

            // init
            setPillState(); updateButtons(); updateProgress();
        })();
    </script>
@endsection
