@extends('layouts.app')
@section('title', $curso->titulo)

@section('head')
    <style>
        /* remove o marcador padrão e gira a setinha quando aberto */
        [data-acc] summary { list-style: none; }
        [data-acc] summary::-webkit-details-marker { display: none; }
        details[open] .acc-arrow { transform: rotate(180deg); }
    </style>
@endsection


@section('content')
    <section class="mx-auto container-page px-4 py-8">


        {{-- Cabeçalho com breadcrumb simplificado --}}
        <div class="flex items-center justify-between mb-3">
            <nav class="text-sm text-slate-500">
                <a href="{{ route('site.cursos') }}" class="hover:underline">Cursos</a>
                <span class="mx-1">/</span>
                <span>{{ $curso->titulo }}</span>
            </nav>

            {{-- Carrinho (link + badge) --}}
            <a href="{{ route('checkout.cart') }}" class="relative inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-md border hover:bg-slate-50">
                <span>🛒</span>
                <span>Carrinho</span>
                <span
                    data-cart-badge
                    class="absolute -top-2 -right-2 hidden min-w-[20px] h-5 px-1 rounded-full bg-blue-600 text-white text-[11px] grid place-items-center"
                >0</span>
            </a>
        </div>


        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Coluna esquerda (mídia + tabs + módulos) --}}
            <div class="lg:col-span-8 space-y-4">
                {{-- Capa/Player --}}
                <div class="rounded-lg border bg-black aspect-video overflow-hidden">
                    @if($curso->video_introducao)
                        <iframe class="w-full h-full" src="{{ $curso->video_introducao }}" allowfullscreen></iframe>
                    @else
                        <img src="{{ $curso->imagem_capa_url }}" class="w-full h-full object-cover opacity-70" alt="Capa do curso">
                    @endif
                </div>

                {{-- Tabs simples (Conteúdo / Sobre / Instrutor / Avaliações) --}}
                <div class="rounded-lg border bg-white">
                    <div class="flex text-sm">
                        <button class="px-4 py-2 border-b-2 border-blue-600 text-blue-700 font-medium">Conteúdo</button>
                        {{--                        <button class="px-4 py-2 text-slate-500">Sobre</button>--}}
                        {{--                        <button class="px-4 py-2 text-slate-500">Instrutor</button>--}}
                        {{--                        <button class="px-4 py-2 text-slate-500">Avaliações</button>--}}
                    </div>

                    {{-- Módulos/Aulas --}}
                    <div class="p-4">
                        <h3 class="font-semibold mb-2">Módulos do Curso</h3>
                        <div class="space-y-3">
                            @forelse($curso->modulos->sortBy('ordem') as $m)
                                <details class="rounded-lg border bg-white" {{ $loop->first ? 'open' : '' }} data-acc>
                                    <summary class="flex items-center justify-between px-4 py-3 cursor-pointer select-none hover:bg-slate-50">
                                        <div class="text-left">
                                            <div class="font-medium">Módulo {{ $loop->iteration }}: {{ $m->titulo }}</div>
                                            @if($m->descricao)
                                                <div class="text-xs text-slate-500">{!! $m->descricao !!}</div>
                                            @endif
                                        </div>
                                        <span class="text-slate-500 text-sm inline-flex items-center gap-1">
                                        <span>Ver aulas</span>
                                        <svg class="w-4 h-4 acc-arrow transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                                        </svg>
                                      </span>
                                    </summary>
                                    @if($m->aulas->count())
                                        <div class="px-4 pb-3">
                                            @foreach($m->aulas->sortBy('ordem') as $a)
                                                <div class="flex items-center justify-between rounded border p-3 mb-2 hover:bg-slate-50">
                                                    <div class="truncate">
                                                        <div class="text-sm">{{ $a->titulo }}</div>
                                                        @if($a->descricao)
                                                            <div class="text-xs text-slate-500 truncate">{!! $a->descricao !!}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </details>
                            @empty
                                <div class="text-sm text-slate-500">Este curso ainda não possui módulos.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>

            {{-- Coluna direita (card de compra) --}}
            <aside class="lg:col-span-4">

                <div class="rounded-lg border p-4 bg-white">
                    <div class="mt-4 space-y-2">
                        <label class="text-sm font-medium">Cupom (opcional)</label>
                        <div class="flex gap-2">
                            <input
                                id="cupomInput"
                                name="cupom"
                                value="{{ request('cupom', session('cupom')) }}"
                                class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                                placeholder="EXEMPLO10">
                            <button id="btnAplicarCupom" class="btn h-10 mt-1">Aplicar</button>
                        </div>
                        <div id="cupomMsg" class="text-sm hidden"></div>
                    </div>

                    @php
                        $temPromo = filled($curso->preco_original) && (float)$curso->preco_original > (float)$curso->preco;
                    @endphp

                    <div class="space-y-2">
                        @if($temPromo)
                            <div class="text-slate-400 text-sm line-through">
                                R$ {{ number_format($curso->preco_original,2,',','.') }}
                            </div>
                        @endif
                        <div class="text-2xl font-bold">
                            <span id="precoAtualSpan">R$ {{ number_format($curso->preco ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            @if((int)($curso->validade_dias ?? 0) > 0)
                                Validade de {{ (int) $curso->validade_dias }} dias
                            @else
                                Acesso vitalício
                            @endif
                        </div>
                    </div>


                    <div class="mt-4 space-y-2">
                        @php
                            $alunoLogado = auth('aluno')->check() || session()->has('aluno_id');
                        @endphp

                        @if(!empty($matriculaVigente))
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                                @if($matriculaVigente->data_vencimento)
                                    Você já possui este curso válido até {{ $matriculaVigente->data_vencimento->format('d/m/Y') }}.
                                @else
                                    Você já possui acesso ativo a este curso.
                                @endif
                            </div>
                            <a
                                href="{{ route('aluno.curso.conteudo', [$curso->id, 'matricula' => $matriculaVigente->id]) }}"
                                class="btn btn-primary w-full"
                            >
                                Acessar curso
                            </a>
                        @elseif($alunoLogado)
                            {{-- Já logado → manda direto pro checkout --}}
                            <form id="buyNowForm" method="GET" action="{{ route('checkout.start', $curso->id) }}">
                                <input type="hidden" name="cupom" id="cupomHidden">
                                <button class="btn btn-primary w-full">Comprar agora</button>
                            </form>

                        @else
                            {{-- Não logado → vai para cadastro com intended + curso --}}
                            <a
                                id="buyNowLink"
                                href="{{ route('aluno.register') }}?intended={{ urlencode(route('checkout.start', $curso->id)) }}&curso={{ $curso->id }}"
                                class="btn btn-primary w-full"
                            >
                                Comprar agora
                            </a>
                        @endif

                        @empty($matriculaVigente)
                            <form id="addToCartForm" method="post" action="{{ route('checkout.cart.add', $curso->id) }}" class="mt-2">
                                @csrf
                                <button class="btn btn-soft w-full">Adicionar ao Carrinho</button>
                            </form>
                        @endempty
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center gap-2"><span>✅</span> Certificado digital reconhecido</div>
                        <div class="flex items-center gap-2"><span>✅</span> Acesso vitalício ao conteúdo</div>
                        <div class="flex items-center gap-2"><span>✅</span> Material complementar em PDF</div>
                        <div class="flex items-center gap-2"><span>✅</span> Suporte especializado</div>
                        <div class="flex items-center gap-2"><span>✅</span> Garantia de 7 dias</div>
                    </div>
                </div>
            </aside>
        </div>
    </section>


    <script>
        (function () {
            const countUrl = "{{ route('checkout.cart.count') }}";
            const addUrl   = "{{ route('checkout.cart.add', $curso->id) }}";
            const cartUrl  = "{{ route('checkout.cart') }}"; // <- usaremos para salvar cupom na sessão
            const token    = "{{ csrf_token() }}";
            const btnAplicar = document.getElementById('btnAplicarCupom');
            const cupomMsg   = document.getElementById('cupomMsg');
            const precoSpan  = document.getElementById('precoAtualSpan');
            const hiddenCupom = document.getElementById('cupomHidden'); // do form buy-now se logado

            const cupomInput  = document.getElementById('cupomInput');
            const buyNowForm  = document.getElementById('buyNowForm');
            const buyNowLink  = document.getElementById('buyNowLink'); // quando não logado
            const addForm     = document.getElementById('addToCartForm');

            function normCupom() {
                const v = (cupomInput?.value || '').trim().toUpperCase();
                return v.length ? v : '';
            }

            function setBadges(count) {
                document.querySelectorAll('[data-cart-badge]').forEach(badge => {
                    const n = Number(count) || 0;
                    badge.textContent = String(n);
                    if (n > 0) badge.classList.remove('hidden'); else badge.classList.add('hidden');
                });
            }

            async function refreshBadge() {
                try {
                    const res = await fetch(countUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    const data = await res.json();
                    setBadges(data?.count ?? 0);
                } catch (e) { /* silencia */ }
            }

            // === Comprar agora (LOGADO): injeta ?cupom= no GET ===
            if (buyNowForm) {
                buyNowForm.addEventListener('submit', () => {
                    const c = normCupom();
                    const hidden = document.getElementById('cupomHidden');
                    if (hidden) hidden.value = c;
                });
            }

            // === Comprar agora (NÃO LOGADO): adiciona cupom na intended ===
            if (buyNowLink) {
                buyNowLink.addEventListener('click', (e) => {
                    const c = normCupom();
                    if (!c) return; // sem cupom, segue normal
                    try {
                        const url = new URL(buyNowLink.href, window.location.origin);
                        const intended = new URL(url.searchParams.get('intended') || '', window.location.origin);
                        intended.searchParams.set('cupom', c); // preserva cupom até o checkout
                        url.searchParams.set('intended', intended.toString());
                        buyNowLink.href = url.toString();
                    } catch(_) { /* ignora em caso de URL inválida */ }
                });
            }

            // Salva cupom na sessão chamando a página do carrinho com ?cupom=
            async function saveCupomToSession() {
                const c = normCupom();
                if (!c) return;
                try {
                    await fetch(`${cartUrl}?cupom=${encodeURIComponent(c)}`, { cache: 'no-store' });
                } catch (_) {}
            }

            // === Adicionar ao carrinho ===
            if (addForm) {
                addForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        const res = await fetch(addUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            cache: 'no-store'
                        });

                        // Após adicionar, gravamos o cupom na sessão (para o fluxo startCart)
                        await saveCupomToSession();

                        const contentType = res.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            await refreshBadge();
                            toast('Curso adicionado ao carrinho.');
                            return;
                        }
                        const data = await res.json();
                        if (data?.ok) {
                            setBadges(data.count ?? 0);
                            toast(data.msg || 'Curso adicionado ao carrinho.');
                        } else {
                            toast(data?.msg || 'Não foi possível adicionar este curso.', true);
                        }
                    } catch (err) {
                        toast('Falha ao adicionar. Tente novamente.', true);
                    }
                });
            }

            function toast(text, isError = false) {
                const el = document.createElement('div');
                el.textContent = text;
                el.className =
                    'fixed bottom-4 left-1/2 -translate-x-1/2 px-3 py-2 rounded text-white text-sm shadow ' +
                    (isError ? 'bg-red-600' : 'bg-blue-600');
                document.body.appendChild(el);
                setTimeout(() => el.remove(), 1800);
            }

            refreshBadge();


            function realBRL(n){
                return (n || 0).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
            }

            async function aplicarCupomItem(){
                const codigo = normCupom();
                if (!codigo) {
                    if (cupomMsg) {
                        cupomMsg.className = 'text-sm text-red-600';
                        cupomMsg.textContent = 'Informe um código de cupom.';
                        cupomMsg.classList.remove('hidden');
                    }
                    return;
                }
                if (btnAplicar) btnAplicar.disabled = true;
                if (cupomMsg) { cupomMsg.classList.add('hidden'); cupomMsg.textContent = ''; }

                try {
                    const url = new URL("{{ route('checkout.cupom.validar_item', $curso->id) }}", window.location.origin);
                    url.searchParams.set('codigo', codigo);

                    const res  = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        if (cupomMsg) {
                            cupomMsg.className = 'text-sm text-red-600';
                            cupomMsg.textContent = data?.mensagem || 'Cupom inválido.';
                            cupomMsg.classList.remove('hidden');
                        }
                        return;
                    }

                    // Atualiza o preço exibido
                    if (precoSpan) precoSpan.textContent = realBRL(data.total);

                    // Guarda o cupom no hidden (para o GET do buy-now logado)
                    if (hiddenCupom) hiddenCupom.value = data.codigo;

                    // Feedback
                    if (cupomMsg) {
                        cupomMsg.className = 'text-sm text-green-700';
                        cupomMsg.textContent = data.mensagem || 'Cupom aplicado.';
                        cupomMsg.classList.remove('hidden');
                    }

                } catch (e) {
                    if (cupomMsg) {
                        cupomMsg.className = 'text-sm text-red-600';
                        cupomMsg.textContent = 'Não foi possível validar o cupom. Tente novamente.';
                        cupomMsg.classList.remove('hidden');
                    }
                } finally {
                    if (btnAplicar) btnAplicar.disabled = false;
                }
            }

            btnAplicar?.addEventListener('click', (e)=>{
                e.preventDefault();
                aplicarCupomItem();
            });
        })();
    </script>

@endsection
