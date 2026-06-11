@extends('layouts.app')

@section('title','Catálogo de Cursos')

@section('content')
    <section class="mx-auto container-page px-4 py-10">
        <h1 class="text-3xl font-extrabold text-center">Catálogo de Cursos</h1>
        <p class="text-center text-slate-600 mt-2">
            Explore nossa biblioteca completa de cursos profissionais e certifique-se com qualidade.
        </p>

        {{-- Busca + Filtros --}}
        <form method="get" class="mt-6">
            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <div class="flex flex-col md:flex-row gap-3 items-center">
                    <div class="relative w-full">
                        <span class="absolute left-3 top-2.5 text-slate-400">🔎</span>
                        <input
                            name="busca"
                            value="{{ request('busca') }}"
                            class="w-full pl-9 pr-3 py-2 border rounded-md focus:ring-2 focus:ring-orange-500/30"
                            placeholder="Buscar cursos..."
                        >
                    </div>

                    {{-- Categoria --}}
                    <select name="categoria" class="w-full md:w-60 border rounded-md py-2 px-3">
                        <option value="">Todas as categorias</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ (string)request('categoria')===(string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->nome }}
                            </option>
                        @endforeach
                    </select>

                    <button class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('site.cursos') }}" class="btn btn-outline">Limpar</a>
                </div>
            </div>
        </form>

        {{-- Grid de cards --}}
        <div class="grid gap-4 mt-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($cursos as $curso)
                @php
                    $minutos = (int) ($curso->carga_horaria_total ?? 0);
                    $horas = $minutos / 60;
                    $horasFmt = fmod($horas, 1.0) === 0.0
                        ? number_format($horas, 0, ',', '.')
                        : number_format($horas, 1, ',', '.');
                @endphp
                <article class="rounded-xl border bg-white overflow-hidden shadow-sm hover:shadow-md transition h-full flex flex-col">
                    {{-- Capa padronizada --}}
                    <div class="bg-slate-100">
                        <img
                            src="{{ $curso->imagem_capa_url }}"
                            alt="Capa do curso {{ $curso->titulo }}"
                            class="w-full h-full object-cover aspect-[16/9]"
                            loading="lazy"
                        >
                    </div>

                    {{-- Conteúdo --}}
                    <div class="p-4 space-y-3 flex-1 flex flex-col">
                        <div class="flex items-center justify-between text-[11px]">
            <span class="px-2 py-1 rounded border border-orange-200 text-orange-700 bg-orange-50">
                {{ $curso->categoria->nome ?? 'Sem categoria' }}
            </span>
                            <span class="px-2 py-1 rounded border border-slate-200 text-slate-600 bg-slate-50">
                {{ $curso->nivel ?? '—' }}
            </span>
                        </div>

                        {{-- Título com clamp (2 linhas) --}}
                        <h3 class="font-semibold leading-snug line-clamp-2" title="{{ $curso->titulo }}">
                            {{ $curso->titulo }}
                        </h3>

                        {{-- Descrição também clampada --}}
                        <p class="text-sm text-slate-600 line-clamp-2" title="{{ $curso->descricao_curta }}">
                            {{ $curso->descricao_curta ?? '' }}
                        </p>

                        <div class="text-xs text-slate-500 flex items-center gap-4">
                            <span>⏱️ {{ $horasFmt }}h</span>
                        </div>

                        {{-- Preço --}}
                        <div class="text-sm">
                            @php
                                $temPromo = isset($curso->preco_original) && (float)$curso->preco_original > (float)$curso->preco;
                            @endphp
                            @if($temPromo)
                                <span class="line-through text-slate-400 mr-1">
                    R$ {{ number_format($curso->preco_original, 2, ',', '.') }}
                </span>
                            @endif
                            <span class="font-semibold text-orange-700">
                R$ {{ number_format($curso->preco, 2, ',', '.') }}
            </span>
                        </div>

                        {{-- empurra o botão para o rodapé do card --}}
                        <div class="mt-auto"></div>

                        <a href="{{ route('site.curso.detalhe', $curso->id) }}" class="btn btn-primary w-full">
                            Ver Detalhes
                        </a>
                    </div>
                </article>


            @empty
                <div class="md:col-span-2 lg:col-span-3 text-center text-slate-500 py-10">
                    Nenhum curso encontrado.
                </div>
            @endforelse
        </div>

        {{-- Paginação --}}
        <div class="mt-6">
            {{ $cursos->appends(request()->query())->links() }}
        </div>
    </section>

    <style>
        .line-clamp-2{
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }
    </style>
@endsection
