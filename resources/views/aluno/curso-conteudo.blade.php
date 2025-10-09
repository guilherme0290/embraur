{{-- resources/views/aluno/curso-conteudo.blade.php --}}
@extends('layouts.app')
@section('title', $curso->titulo)

@section('head')
    <style>
        /* mídia do CKEditor responsiva dentro do artigo */
        .prose iframe,
        .prose video {
            width: 100%;
            height: auto;
            aspect-ratio: 16/9;
            display: block;
        }

        .prose img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: .375rem; /* opcional */
        }

        /* figuras do CKEditor ficam com margem bonitinha */
        .prose figure {
            margin: 1rem 0;
        }

        .prose figure > figcaption {
            font-size: .875rem;
            color: rgb(100 116 139);
            text-align: center;
            margin-top: .25rem;
        }
    </style>
@endsection

@section('content')
    <div class="container mx-auto py-6">

        <div class="mb-3">
            <a href="{{ route('aluno.dashboard') }}" class="text-slate-500 hover:underline">
                &larr; Voltar ao Dashboard
            </a>
        </div>

        <h1 class="text-2xl font-bold mb-4">{{ $curso->titulo }}</h1>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- COLUNA PRINCIPAL (PLAYER + CONTEÚDO) --}}
            <div class="lg:col-span-8 space-y-4">
                @php
                    use App\Support\CursoGate;use Illuminate\Support\Str;

                    $tipo    = Str::lower($aula->tipo ?? '');
                    $hasHtml = filled($aula->conteudo_texto);    // CKEditor
                    $isVideo = !$hasHtml && $tipo === 'video' && filled($aula->conteudo_url); // só se não tiver HTML
                    $src     = trim((string)($aula->conteudo_url ?? ''));

                    if ($isVideo && $src) {
                        if (Str::contains($src, 'youtu.be/')) {
                            $id  = Str::after($src, 'youtu.be/'); $id = Str::before($id, '?');
                            $src = 'https://www.youtube.com/embed/' . $id;
                        } elseif (Str::contains($src, 'watch?v=')) {
                            $id  = Str::after($src, 'v='); $id = Str::before($id, '&');
                            $src = 'https://www.youtube.com/embed/' . $id;
                        } elseif (Str::contains($src, 'vimeo.com/')) {
                            if (preg_match('~vimeo\.com/(\d+)~', $src, $m)) {
                                $src = 'https://player.vimeo.com/video/' . $m[1];
                            }
                        }
                        if (Str::contains($src, 'youtube.com/embed/')) {
                            $src .= (Str::contains($src, '?') ? '&' : '?') . 'enablejsapi=1&rel=0';
                        }
                    }
                @endphp

                <div class="rounded-lg border bg-white overflow-hidden">
                    @if($hasHtml)
                        {{-- CKEditor: texto + imagens + vídeos juntos --}}
                        <article class="prose prose-slate max-w-none p-6">
                            {!! $aula->conteudo_texto !!}
                        </article>
                    @elseif($isVideo)
                        {{-- Player “puro” quando não há conteúdo CKEditor --}}
                        <div class="relative aspect-video bg-black">
                            @if(Str::contains($src, ['youtube.com','youtu.be','vimeo.com','player.vimeo.com']))
                                <iframe
                                    class="absolute inset-0 w-full h-full"
                                    src="{{ $src }}"
                                    allow="autoplay; fullscreen; picture-in-picture"
                                    allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin"
                                ></iframe>
                            @else
                                <video class="absolute inset-0 w-full h-full" src="{{ $src }}" controls
                                       playsinline></video>
                            @endif
                        </div>
                    @else
                        <div class="p-6 text-slate-400">Conteúdo da aula</div>
                    @endif
                </div>


                {{-- Título + Navegação --}}
                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">{{ $aula->titulo }}</h2>
                            <div class="text-xs text-slate-500">{{ $aula->duracao_minutos }}min</div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($prevAula)
                                <a href="{{ route('aluno.curso.modulo.aula', [$curso->id, $modulo->id, $prevAula->id]) }}"
                                   class="px-3 py-2 border rounded hover:bg-slate-50">&larr; Anterior</a>
                            @else
                                <button class="px-3 py-2 border rounded opacity-50" disabled>&larr; Anterior</button>
                            @endif

                            @if($nextAula)
                                {{-- Próxima aula dentro do mesmo módulo --}}
                                <a href="{{ route('aluno.curso.modulo.aula', [$curso->id, $modulo->id, $nextAula->id]) }}"
                                   class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    Próxima &rarr;
                                </a>
                            @elseif($modulo->quiz)
                                {{-- Se não tem próxima aula, mas tem prova do módulo --}}
                                <a href="{{ route('aluno.quiz.show', [$curso->id, $modulo->quiz->id]) }}"
                                   class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    Ir para a Prova do Módulo &rarr;
                                </a>
                            @elseif(!empty($nextAulaCross))
                                {{-- Próxima aula no próximo módulo --}}
                                <a href="{{ route('aluno.curso.modulo.aula', [$curso->id, $nextAulaCross->modulo_id, $nextAulaCross->id]) }}"
                                   class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    Próxima &rarr;
                                </a>
                            @else
                                <button class="px-3 py-2 bg-green-600 text-white rounded opacity-50" disabled>
                                    Fim do Módulo
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Material de Apoio --}}
                <div class="rounded-lg border p-4">
                    <h3 class="font-semibold mb-3">Material de Apoio</h3>
                    @php
                        $materiais = method_exists($aula,'materiais') ? $aula->materiais : collect();
                    @endphp

                    @if($materiais->count())
                        <ul class="space-y-2">
                            @foreach($materiais as $m)
                                <li class="flex items-center justify-between rounded border p-3">
                                    <span class="truncate">{{ $m->titulo ?? 'Arquivo' }}</span>
                                    <a href="{{ $m->arquivo_url ?? '#' }}" target="_blank"
                                       class="px-2 py-1 border rounded hover:bg-slate-50 text-sm">Baixar</a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-slate-500 text-sm">Nenhum material cadastrado.</div>
                    @endif
                </div>
            </div>

            {{-- SIDEBAR (CONTEÚDO DO CURSO) --}}
            <div class="lg:col-span-4">
                <div class="rounded-lg border p-4">
                    <h3 class="font-semibold mb-3">Conteúdo do Curso</h3>

                    @foreach($curso->modulos->sortBy('ordem') as $idx => $m)
                        @php
                            $isAtual = (int)$m->id === (int)$modulo->id;
                            $quiz    = $m->quiz ?? null;
                            $tent    = $quiz ? ($ultimaTentativaPorQuiz[$quiz->id] ?? null) : null;

                            $statusQuiz = 'pend';
                            if ($tent) {
                                $statusQuiz = $tent->aprovado ? 'ok' : 'reprov';
                            }

                            // Trava visual do próximo módulo quando anterior não aprovado
                            $modLiberado = true;
                            if (class_exists(CursoGate::class)) {
                                $modLiberado = CursoGate::podeAcessarModulo($curso, $matricula, $idx);
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
                                    <span class="text-[11px] text-green-600">Atual</span>
                                @endif
                            </div>
                            @if($m->descricao)
                                <div class="text-xs text-slate-500">{!! $m->descricao !!}</div>
                            @endif

                            <div class="mt-2">
                                @foreach($m->aulas->sortBy('ordem') as $a)
                                    <a href="{{ route('aluno.curso.modulo.aula', [$curso->id, $m->id, $a->id]) }}"
                                       class="flex items-center justify-between rounded border p-2 mb-1 {{ !$modLiberado ? 'opacity-60 pointer-events-none' : '' }} {{ (int)$a->id === (int)$aula->id ? 'bg-green-50 border-green-200' : 'hover:bg-slate-50' }}">
                                        <span class="truncate text-sm">{{ $a->titulo }}</span>
                                        <span class="text-xs text-slate-500">{{ $a->duracao_minutos }}min</span>
                                    </a>
                                @endforeach

                                {{-- Prova do módulo --}}
                                <div class="flex items-center justify-between mt-2">
                                    @if($quiz)
                                        <a href="{{ route('aluno.quiz.show', [$curso->id, $quiz->id]) }}"
                                           class="px-2 py-1 text-sm border rounded hover:bg-slate-50 {{ !$modLiberado ? 'opacity-60 pointer-events-none' : '' }}">
                                            Prova do Módulo
                                        </a>
                                        @switch($statusQuiz)
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

                                {{-- Cadeado / aviso para próximo módulo --}}
                                @if(!$modLiberado)
                                    <div class="mt-2 text-[11px] text-amber-700">
                                        Para acessar este módulo, conclua e seja aprovado na prova do módulo anterior.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- Bloco: Certificado (sidebar) --}}
                @php

                    // Regra simples: habilita ao concluir (CourseCompletionService seta status=concluido)
                    $podeEmitirCert = ($matricula->status ?? null) === 'concluido';
                @endphp

                <div class="mt-4 rounded-lg border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold">Certificado</div>
                            <div class="text-xs text-slate-600 mt-0.5">
                                @if($certificado)
                                    Emitido em {{ optional($certificado->data_emissao)->format('d/m/Y') }}.
                                @elseif(!$podeEmitirCert)
                                    Conclua todas as aulas/provas para liberar a emissão.
                                @else
                                    Parabéns! Você já pode emitir o certificado.
                                @endif
                            </div>
                        </div>

                        @if($certificado)

                            <form method="POST"
                                  action="{{ route('aluno.curso.certificado.baixar', [$curso->id, 'certificado' => $certificado->id]) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 whitespace-nowrap">
                                    Baixar PDF
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('aluno.curso.certificado.emitir', $curso->id) }}">
                                @csrf
                                <button
                                    class="px-3 py-2 rounded whitespace-nowrap {{ $podeEmitirCert ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                    {{ $podeEmitirCert ? '' : 'disabled' }}>
                                    Emitir Certificado
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection

<script>
    (function () {

        const aulaId = {{ (int) $aula->id }};
        const tipo = "{{ $tipo }}"; // 'video' ou 'texto'
        const isYT = {{ Str::contains($src ?? '', 'youtube.com/embed/') ? 'true' : 'false' }};
        const isVM = {{ Str::contains($src ?? '', 'player.vimeo.com') ? 'true' : 'false' }};

        function getCookie(name) {
            return document.cookie.split('; ').reduce((acc, cur) => {
                const [k, ...v] = cur.split('=');
                return k === name ? decodeURIComponent(v.join('=')) : acc;
            }, '');
        }

        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content
                || getCookie('XSRF-TOKEN'); // fallback (Laravel define esse cookie)
        }

        // POST utilitário
        async function postProgress(aulaId, segundos, duracao, marcar = false) {
            const csrf = getCsrf();
            const url = `{{ url('/aluno/aulas') }}/${aulaId}/progresso`;

            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',               // envia cookies da sessão
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,                   // meta/cookie token
                    'X-Requested-With': 'XMLHttpRequest',   // padrão Laravel para AJAX
                },
                body: JSON.stringify({
                    segundos_assistidos: Math.max(0, Math.floor(segundos || 0)),
                    duracao_total: Math.max(1, Math.floor(duracao || 1)),
                    marcar_concluida: marcar ? 1 : 0,
                }),
            });
        }

        window.postProgress = postProgress;

        // Throttle para não spammar
        let lastSentAt = 0;
        const SEND_EVERY_MS = 10000; // 10s
        function maybeSend(now, segundos, duracao) {
            if (now - lastSentAt >= SEND_EVERY_MS) {
                lastSentAt = now;
                postProgress(segundos, duracao, false);
            }
        }

        // Caso TEXTO: marcar concluído ao abrir (simples e claro em UX)
        if (tipo === 'texto') {
            // envie uma única vez ao abrir
            setTimeout(() => postProgress(1, 1, true), 500);

            // (opcional) só marcar quando rolar 80%:
            const scroller = document.querySelector('.aspect-video .overflow-auto');
            if (scroller) {
                let done = false;
                scroller.addEventListener('scroll', () => {
                    if (done) return;
                    const pct = (scroller.scrollTop + scroller.clientHeight) / scroller.scrollHeight;
                    if (pct >= 0.8) {
                        done = true;
                        postProgress(1, 1, true);
                    }
                });
            }
            return; // nada mais a fazer para texto
        }

        // Caso VÍDEO:
        // 1) MP4 <video>
        const videoEl = document.querySelector('.aspect-video video');
        if (videoEl) {
            const getDur = () => isFinite(videoEl.duration) && videoEl.duration > 0 ? videoEl.duration : ({{ (int)($aula->duracao_minutos ?? 0) }} * 60) || 1;

            videoEl.addEventListener('timeupdate', () => {
                maybeSend(performance.now(), videoEl.currentTime, getDur());
            });

            videoEl.addEventListener('ended', () => {
                postProgress(getDur(), getDur(), true);
            });

            // fallback: se o usuário pausar depois de um tempo
            let ping = setInterval(() => {
                if (!document.body.contains(videoEl)) return clearInterval(ping);
                if (!videoEl.paused && !videoEl.seeking) {
                    maybeSend(performance.now(), videoEl.currentTime, getDur());
                }
            }, 3000);

            return;
        }

        // 2) YouTube IFrame API
        const ytIframe = isYT ? document.querySelector('.aspect-video iframe[src*="youtube.com/embed/"]') : null;
        if (ytIframe) {
            // carrega API se necessário
            if (!window.YT) {
                const s = document.createElement('script');
                s.src = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(s);
            }
            window.onYouTubeIframeAPIReady = function () {
                const player = new YT.Player(ytIframe, {
                    events: {
                        onReady: () => {
                            // loop de coleta
                            const tick = setInterval(() => {
                                try {
                                    const dur = player.getDuration() || ({{ (int)($aula->duracao_minutos ?? 0) }} * 60) || 1;
                                    const cur = player.getCurrentTime() || 0;
                                    maybeSend(performance.now(), cur, dur);
                                } catch (e) {
                                }
                                if (!document.body.contains(ytIframe)) clearInterval(tick);
                            }, 3000);
                        },
                        onStateChange: (e) => {
                            if (e.data === YT.PlayerState.ENDED) {
                                const dur = player.getDuration() || ({{ (int)($aula->duracao_minutos ?? 0) }} * 60) || 1;
                                postProgress(dur, dur, true);
                            }
                        }
                    }
                });
            };
            return;
        }

        // 3) Vimeo player.js
        const vmIframe = isVM ? document.querySelector('.aspect-video iframe[src*="player.vimeo.com"]') : null;
        if (vmIframe) {
            // carrega lib
            (function loadVM() {
                if (window.Vimeo && window.Vimeo.Player) return initVM();
                const s = document.createElement('script');
                s.src = 'https://player.vimeo.com/api/player.js';
                s.onload = initVM;
                document.head.appendChild(s);
            })();

            function initVM() {
                const player = new Vimeo.Player(vmIframe);
                let last = 0, durCache = 0;

                player.getDuration().then(d => durCache = d || ({{ (int)($aula->duracao_minutos ?? 0) }} * 60) || 1);

                player.on('timeupdate', (data) => {
                    const now = performance.now();
                    const cur = data.seconds || 0;
                    const dur = durCache || data.duration || 1;
                    maybeSend(now, cur, dur);
                });

                player.on('ended', () => {
                    const dur = durCache || 1;
                    postProgress(dur, dur, true);
                });
            }
        }
    })();
</script>

