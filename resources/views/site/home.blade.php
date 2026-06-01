@extends('layouts.app')

@section('title','Embraur')

@section('content')
    {{-- Hero --}}
    <section
        class="relative bg-cover bg-center"
        style="background-image: url('{{ asset('storage/images/backgroud.png') }}')"
    >
        {{-- overlay com gradient “brand” --}}
        <div class="absolute inset-0 bg-gradient-to-r from-[#2f3528]/90 via-[#2f3528]/70 to-[#2f3528]/40"></div>

        <div class="relative mx-auto container-page px-4 py-24 md:py-28">
            <div class="max-w-3xl rounded-2xl border border-white/10 bg-white/5 backdrop-blur-md text-white p-6 md:p-8 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)] ring-1 ring-white/10">
            <span class="inline-block text-[11px] tracking-wide uppercase bg-white/15 px-2.5 py-1 rounded-full">
                Mais de 1000 alunos certificados
            </span>

                <h1 class="mt-4 text-4xl md:text-5xl font-extrabold leading-tight">
                    Transforme sua carreira com<br>
                    <span class="text-[#c1cab0]">cursos de qualidade</span>
                </h1>

                <p class="mt-4 max-w-2xl text-white/90">
                    Certificações reconhecidas pelo mercado, metodologia comprovada e suporte completo para seu desenvolvimento profissional.
                </p>


            </div>
        </div>
    </section>

    {{-- Cursos Populares --}}
    <section class="py-12">
        <div class="mx-auto container-page px-4">
            <h2 class="text-2xl font-bold text-center">CURSOS POPULARES</h2>
            <p class="text-center text-slate-600 mt-1">Descubra os cursos mais procurados.</p>

            <div class="grid md:grid-cols-4 gap-4 mt-6">
                @foreach ($populares as $curso)
                    @php
                        $minutos = (int) ($curso->carga_horaria_total ?? 0);
                        $horas = $minutos / 60;
                        $horasFmt = fmod($horas, 1.0) === 0.0
                            ? number_format($horas, 0, ',', '.')
                            : number_format($horas, 1, ',', '.');
                    @endphp
                    <div class="group card h-full flex flex-col overflow-hidden rounded-xl border border-slate-200
            hover:border-[#cdd5bf] hover:ring-2 hover:ring-[#cdd5bf]/60 hover:shadow-xl
            transition-all duration-300">
                        <div class="h-32 overflow-hidden">
                            <img src="{{ $curso->imagem_capa_url }}" alt="Capa do curso {{ $curso->titulo }}"
                                 class="w-full h-full object-cover transform group-hover:scale-[1.03] transition-transform duration-500">
                        </div>

                        <div class="p-4 flex-1 flex flex-col">
                            <div class="flex items-center justify-between text-xs">
                                <span class="badge border-[#d5dcc9] text-[#606d50] bg-[#f5f7f2]">{{ $curso->categoria->nome }}</span>
                                <span class="badge border-slate-200 text-slate-600 bg-slate-50">{{ $curso->nivel }}</span>
                            </div>

                            {{-- TÍTULO: reserva espaço para até 2 linhas --}}
                            <h3 class="mt-1 font-semibold leading-snug line-clamp-2 min-h-[3.25rem]" title="{{ $curso->titulo }}">
                                {{ $curso->titulo }}
                            </h3>

                            <div class="text-xs text-slate-500 flex items-center gap-3">
                                <span><i class="ri-time-line mr-1"></i> {{ $horasFmt }}h</span>
                            </div>

                            {{-- empurra o rodapé (preço + botão) para o fim do card --}}
                            <div class="mt-auto pt-2 space-y-3">
                                <div class="flex items-baseline gap-2">
                                    @if($curso->preco_original)
                                        <span class="line-through text-slate-400">
            R$ {{ number_format($curso->preco_original,2,',','.') }}
          </span>
                                    @endif
                                    <span class="text-lg font-bold text-[#606d50]">
          R$ {{ number_format($curso->preco,2,',','.') }}
        </span>
                                </div>

                                <a href="{{ route('site.curso.detalhe',$curso->id) }}"
                                   class="btn btn-primary w-full rounded-lg font-semibold hover:translate-y-[-1px] transition-transform">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    </div>

                @endforeach
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('site.cursos') }}" class="btn btn-outline">VER TODOS OS CURSOS</a>
            </div>
        </div>
    </section>

    {{-- Parceiros (dinâmico) --}}
    <section class="bg-white">
        <div class="mx-auto container-page px-4 py-12">
            <h2 class="text-2xl font-bold text-center">EMPRESAS QUE CONFIAM NO NOSSO TRABALHO</h2>

            @if($parceiros->isEmpty())
                <p class="text-center text-slate-500 mt-4">Em breve novos parceiros por aqui.</p>
            @else
                <div class="mt-6 relative overflow-hidden">
                    {{-- masks de fade nas laterais --}}
                    <div class="pointer-events-none absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-white to-transparent"></div>
                    <div class="pointer-events-none absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-white to-transparent"></div>

                    <div class="parceiros-track flex items-center gap-12 will-change-transform"
                         style="animation-duration: {{ max(18, 6 + $parceiros->count()*3) }}s">
                        {{-- Linha A --}}
                        <div class="parceiros-row flex items-center gap-12 shrink-0">
                            @foreach ($parceiros as $p)
                                @php $src = asset('storage/images/parceiros/'.$p['logo']); @endphp
                                @if(!empty($p['url']))
                                    <a href="{{ $p['url'] }}" target="_blank" rel="noopener"
                                       class="block opacity-90 hover:opacity-100 transition">
                                        <img src="{{ $src }}" alt="{{ $p['alt'] ?? 'Parceiro' }}"
                                             class="h-12 object-contain grayscale hover:grayscale-0 transition" loading="lazy">
                                    </a>
                                @else
                                    <img src="{{ $src }}" alt="{{ $p['alt'] ?? 'Parceiro' }}"
                                         class="h-12 object-contain grayscale hover:grayscale-0 transition" loading="lazy">
                                @endif
                            @endforeach
                        </div>
                        {{-- Linha B (duplicada) --}}
                        <div class="parceiros-row flex items-center gap-12 shrink-0">
                            @foreach ($parceiros as $p)
                                @php $src = asset('storage/images/parceiros/'.$p['logo']); @endphp
                                @if(!empty($p['url']))
                                    <a href="{{ $p['url'] }}" target="_blank" rel="noopener"
                                       class="block opacity-90 hover:opacity-100 transition">
                                        <img src="{{ $src }}" alt="{{ $p['alt'] ?? 'Parceiro' }}"
                                             class="h-12 object-contain grayscale hover:grayscale-0 transition" loading="lazy">
                                    </a>
                                @else
                                    <img src="{{ $src }}" alt="{{ $p['alt'] ?? 'Parceiro' }}"
                                         class="h-12 object-contain grayscale hover:grayscale-0 transition" loading="lazy">
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>


                {{-- Pontinhos decorativos (opcional) --}}
                <div class="flex justify-center mt-6 gap-2">
                    @for($i=0; $i<5; $i++)
                        <span class="w-2 h-2 rounded-full bg-slate-300 inline-block"></span>
                    @endfor
                </div>
            @endif
        </div>
    </section>

    {{-- SOBRE --}}
    <section id="sobre" class="bg-[#f5f7f2]">
        <div class="mx-auto container-page px-4 py-12 grid md:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-2xl font-bold">Sobre</h2>
                <p class="mt-3 text-slate-700">
                    Cursos em segurança do trabalho.
                    Somos uma plataforma especializada em cursos de capacitação voltados para a área de Segurança do Trabalho,
                    com foco nas principais Normas Regulamentadoras exigidas pelo Ministério do Trabalho.
                    Conteúdo atualizado, didático e certificado para garantir a qualificação e segurança dos profissionais.
                </p>

                <h3 class="mt-6 font-semibold">Nossos principais cursos</h3>
                <ul class="mt-2 space-y-2 text-slate-700">
                    <li><span class="font-medium">NR 10 – Segurança em Instalações e Serviços com Eletricidade:</span>
                        capacita profissionais para atuarem com segurança em instalações elétricas.</li>
                    <li><span class="font-medium">NR 10 SEP – Sistema Elétrico de Potência:</span>
                        complementar ao NR 10 para quem trabalha diretamente em SEP e suas proximidades.</li>
                    <li><span class="font-medium">NR 35 – Trabalho em Altura:</span>
                        técnicas e procedimentos seguros para atividades acima de 2 metros.</li>
                </ul>
            </div>

            <div class="rounded-xl overflow-hidden shadow border bg-white p-6">
                <img src="https://blog.mrhgestao.com.br/wp-content/uploads/2018/03/178698-tecnico-em-seguranca-do-trabalho-conheca-o-mercado-no-brasil.jpg"
                     alt="Treinamento de segurança do trabalho" class="w-full h-56 object-cover rounded-lg">
                <div class="mt-4 text-sm text-slate-600">
                    Certificados reconhecidos • Conteúdo atualizado • Acesso 24/7
                </div>
            </div>
        </div>
    </section>

    {{-- CONTATO --}}
    {{-- CONTATO (premium) --}}
    <section id="contato" class="relative">
        {{-- fundo suave com gradient --}}
        <div class="absolute inset-0 bg-gradient-to-b from-white via-[#f8faf7] to-white"></div>

        <div class="relative mx-auto container-page px-4 py-16">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">Fale Conosco</h2>
                <p class="mt-2 text-slate-600">Estamos prontos para ajudar você a escolher o curso ideal.</p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                {{-- WhatsApp --}}
                <div class="group rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl grid place-items-center bg-emerald-50 text-emerald-600">
                                <i class="ri-whatsapp-line text-xl"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">WhatsApp</div>
                                <div class="font-semibold text-slate-900">(48) 3198-3198</div>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-600">
                            Atendimento rápido no horário comercial. Respostas em poucos minutos.
                        </p>

                        <div class="mt-4 flex items-center gap-2">
                            <a href="https://wa.me/554831983198?text=Ol%C3%A1!%20Preciso%20de%20suporte%20no%20site%20Embraur."
                               target="_blank" rel="noopener"
                               class="btn btn-primary !rounded-lg inline-flex items-center gap-2">
                                <i class="ri-send-plane-line"></i> Iniciar conversa
                            </a>
                            <button type="button" data-copy="(48) 3198-3198"
                                    class="btn btn-outline !rounded-lg inline-flex items-center gap-2">
                                <i class="ri-file-copy-line"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- E-mail --}}
                <div class="group rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl grid place-items-center bg-sky-50 text-sky-600">
                                <i class="ri-mail-line text-xl"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">E-mail</div>
                                <div class="font-semibold text-slate-900 break-all">embraur@embraur.com.br</div>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-600">
                            Envie sua dúvida, retorno em até 1 dia útil.
                        </p>

                        <div class="mt-4 flex items-center gap-2">
                            <a href="mailto:embraur@embraur.com.br"
                               class="btn btn-primary !rounded-lg inline-flex items-center gap-2">
                                <i class="ri-arrow-right-up-line"></i> Escrever e-mail
                            </a>
                            <button type="button" data-copy="embraur@embraur.com.br"
                                    class="btn btn-outline !rounded-lg inline-flex items-center gap-2">
                                <i class="ri-file-copy-line"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Redes sociais --}}
                <div class="group rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl grid place-items-center bg-fuchsia-50 text-fuchsia-600">
                                <i class="ri-instagram-line text-xl"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Redes Sociais</div>
                                <div class="font-semibold text-slate-900">@embraur</div>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-600">
                            Acompanhe novidades, promoções e bastidores dos cursos.
                        </p>

                        <div class="mt-4 flex items-center gap-2">
                            <a href="https://www.instagram.com/embraur" target="_blank" rel="noopener"
                               class="btn btn-primary !rounded-lg inline-flex items-center gap-2">
                                <i class="ri-external-link-line"></i> Abrir Instagram
                            </a>
                            <button type="button" onclick="window.open('https://www.instagram.com/embraur','_blank')"
                                    class="btn btn-outline !rounded-lg">
                                Seguir
                            </button>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </section>

    {{-- utilitário: copiar para área de transferência --}}
    <script>
        document.querySelectorAll('[data-copy]').forEach(btn=>{
            btn.addEventListener('click', async ()=>{
                try {
                    await navigator.clipboard.writeText(btn.getAttribute('data-copy'));
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="ri-check-line"></i> Copiado!';
                    setTimeout(()=> btn.innerHTML = original, 1500);
                } catch(e) {}
            });
        });
    </script>




    @php
        $miniCart = collect(session('cart', []));
        $miniTotal = $miniCart->sum('preco');
    @endphp

    {{-- MINI-CARRINHO --}}

    <button id="miniCartToggle"
            class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 px-4 py-2.5
               rounded-full border border-white/20 bg-white/70 backdrop-blur-md
               shadow-lg hover:shadow-xl hover:bg-white/80
               text-slate-800 transition-all">
        <span class="font-medium">Carrinho</span>
        <span data-cart-badge
              class="min-w-[22px] h-[22px] px-1.5 rounded-full bg-[#778663] text-white text-[11px] grid place-items-center {{ $miniCart->isEmpty() ? 'hidden' : '' }}">
        {{ $miniCart->count() }}
    </span>
    </button>

    <div id="miniCartPanel"
         class="fixed bottom-20 right-5 z-40 w-[320px] max-h-[70vh] overflow-auto rounded-xl border bg-white shadow-lg hidden">
        <div class="p-4 border-b flex items-center justify-between">
            <div class="font-semibold">Seu carrinho</div>
            <button class="text-slate-500 hover:text-slate-700" onclick="document.getElementById('miniCartPanel').classList.add('hidden')">✕</button>
        </div>

        <div class="p-3">
            @if($miniCart->isEmpty())
                <div class="text-sm text-slate-500 p-3">Seu carrinho está vazio.</div>
            @else
                <ul class="space-y-2">
                    @foreach($miniCart as $it)
                        <li class="flex items-center justify-between rounded border p-2">
                            <div class="pr-2">
                                <div class="text-sm font-medium truncate max-w-[180px]">{{ $it['titulo'] }}</div>
                                <div class="text-xs text-slate-500">R$ {{ number_format($it['preco'] ?? 0,2,',','.') }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="post" action="{{ route('checkout.cart.remove', $it['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">remover</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 border-t pt-3 flex items-center justify-between">
                    <span class="text-sm text-slate-600">Total</span>
                    <span class="font-semibold">R$ {{ number_format($miniTotal,2,',','.') }}</span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="{{ route('checkout.cart') }}" class="btn btn-outline text-center">Ver carrinho</a>
                    <a href="{{ route('checkout.cart') }}" class="btn btn-primary text-center">Finalizar</a>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function(){
            const btn = document.getElementById('miniCartToggle');
            const panel = document.getElementById('miniCartPanel');
            btn?.addEventListener('click', ()=> panel.classList.toggle('hidden'));

            async function refreshCartBadge() {
                try {
                    const res = await fetch("{{ route('checkout.cart.count') }}", { headers: {'Accept':'application/json'}, cache: 'no-store' });
                    const data = await res.json();
                    const n = Number(data?.count || 0);
                    document.querySelectorAll('[data-cart-badge]').forEach(b=>{
                        b.textContent = String(n);
                        b.classList.toggle('hidden', n === 0);
                    });
                } catch(e){}
            }
            refreshCartBadge();
        })();
    </script>

    <style>
        @keyframes parceiros-scroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
        .parceiros-track{ width:max-content; animation-name:parceiros-scroll; animation-timing-function:linear; animation-iteration-count:infinite; will-change:transform; }
        .parceiros-track:hover{ animation-play-state:paused; }

        /* Botão primário: glow discreto */
        .btn.btn-primary:hover{ box-shadow:0 10px 30px -10px rgba(96,109,80,.45); }
    </style>
@endsection
