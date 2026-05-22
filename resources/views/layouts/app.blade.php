<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'Embraur')</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/images/favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">


    {{-- mantém o seu vite (se já estiver ok, segue usando) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
{{--    ,'resources/js/ckeditor.js'--}}


    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- fallback SEM instalar nada: deixa tudo bonito mesmo se o vite não carregar --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .container-page{max-width:1100px;margin:0 auto;padding-left:1rem;padding-right:1rem}
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;padding:.5rem .9rem;font-weight:600;border:1px solid transparent;line-height:1}
        .btn-primary{background:#889875;color:#fff;border-color:#889875}
        .btn-primary:hover{background:#7b896b;border-color:#7b896b}
        .btn-outline{background:#fff;color:#0f172a;border-color:#cbd5e1}.btn-outline:hover{background:#f8fafc}
        .btn-soft{background:#f1f5f9;color:#0f172a}.btn-soft:hover{background:#e2e8f0}
    </style>
    <style>html{scroll-behavior:smooth}</style>
</head>
@stack('scripts')


<body class="bg-slate-50 text-slate-800">
{{-- Header ÚNICO --}}
<header class="bg-white border-b">
    <div class="container-page flex items-center justify-between h-14">
        <a href="{{ route('site.home') }}" class="flex items-center gap-2 font-semibold">
            <img src="{{ asset('storage/images/logo.png') }}" alt="Logo Embraur" class="h-8 w-auto">
        </a>


        <nav class="hidden md:flex items-center gap-6 text-sm">
            <a href="{{ route('site.home') }}" class="hover:text-blue-600">INICIO</a>
            <a href="{{ route('site.cursos') }}" class="hover:text-blue-600">CURSOS</a>
            <a  href="{{ url('/#sobre') }}" class="hover:text-blue-600">SOBRE NÓS</a>
            <a href="{{ url('/#contato') }}" class="hover:text-blue-600">CONTATO</a>
        </nav>





        <div class="flex items-center gap-2">
            <a href="{{ route('portal.aluno') }}" class="hidden sm:inline-flex btn btn-soft">PORTAL DO ALUNO</a>
            <a href="{{ route('portal.professor') }}" class="hidden sm:inline-flex btn btn-outline">PORTAL DO PROFESSOR</a>

            @if(session('aluno_id'))
                <form id="logoutForm" action="{{ route('aluno.logout') }}" method="POST" class="hidden md:inline-flex">
                    @csrf
                    <button type="submit" class="btn-primary h-9 px-4 rounded-md">Sair</button>
                </form>
            @endif
        </div>
    </div>
</header>

@if (session('success') || session('error') || session('info'))
    <div class="container-page max-w-5xl mx-auto mt-4">
        @if (session('success'))
            <div class="mb-3 rounded-lg border border-blue-200 bg-blue-50 text-blue-900 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if (session('info'))
            <div class="mb-3 rounded-lg border border-blue-200 bg-blue-50 text-blue-900 px-4 py-3">
                {{ session('info') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 text-red-900 px-4 py-3">
                {{ session('error') }}
            </div>
        @endif
    </div>
@endif

{{-- VALIDAÇÃO: lista de erros (mostra até 5) --}}
@if ($errors->any() && !request()->routeIs('prof.cursos.create', 'prof.cursos.store', 'prof.cursos.edit', 'prof.cursos.update'))
    @php
        $all = $errors->all();                     // array de mensagens
        $top = collect($all)->take(5);             // pega só as 5 primeiras
    @endphp
    <div class="container-page max-w-5xl mx-auto mt-2">
        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3">
            <strong class="block mb-1">Corrija os campos abaixo:</strong>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($top as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if (count($all) > 5)
                    <li>… e mais {{ count($all) - 5 }} erro(s).</li>
                @endif
            </ul>
        </div>
    </div>
@endif

<main>@yield('content')</main>

{{-- Footer ÚNICO --}}
<footer class="mt-10 border-t bg-white">
    <div class="container-page py-8 grid grid-cols-1 md:grid-cols-4 gap-8 text-sm">
        <div>
            <div class="font-semibold mb-2 flex items-center gap-2">
                <img src="{{ asset('storage/images/logo.png') }}" alt="Logo Embraur" class="h-6 w-auto">
            </div>
            <p class="text-slate-600">Plataforma completa de ensino a distância com cursos de qualidade e certificação reconhecida.</p>
        </div>
        <div>
            <div class="font-semibold mb-2">Links Rápidos</div>
            <ul class="space-y-1 text-slate-600">
                <li>
                    <a class="hover:text-blue-600" href="{{ route('site.cursos') }}">
                        Catálogo de Cursos
                    </a>
                </li>
                <li>
                    <a class="hover:text-blue-600" href="{{ url('/#sobre') }}">
                        Sobre Nós
                    </a>
                </li>
                <li>
                    <a class="hover:text-blue-600" href="{{ url('/#contato') }}">
                        Contato
                    </a>
                </li>
            </ul>
        </div>
        <div>
            <div class="font-semibold mb-2">Área do Aluno</div>
            <ul class="space-y-1 text-slate-600">
                <li><a class="hover:text-blue-600" href="{{ route('aluno.login') }}">Login</a></li>
                <li><a class="hover:text-blue-600" href="{{ route('aluno.register') }}">Cadastro</a></li>
                <li><a class="hover:text-blue-600" href="{{ route('aluno.cursos') }}">Meus Cursos</a></li>
                <li><a class="hover:text-blue-600" href="{{ route('aluno.certificados') }}">Certificados</a></li>
            </ul>
        </div>
        <div>
            <div class="font-semibold mb-2">Contato</div>
            <ul class="space-y-1 text-slate-600">
                <li>embraur@embraur.com.br</li><li>(48) 3198-3198</li>
            </ul>
        </div>
    </div>
    <div class="text-center text-xs text-slate-500 py-4 border-t">© 2025 Embraur. Todos os direitos reservados.</div>

    {{-- WHATSAPP FLUTUANTE (lado direito) --}}
    <div class="fixed right-5 bottom-5 flex flex-col items-end gap-3 z-50">
        {{-- whatsapp --}}
        <a href="https://wa.me/554831983198"
           target="_blank" rel="noopener"
           class="w-14 h-14 rounded-full bg-[#25D366] grid place-items-center text-white shadow-lg">
            <svg viewBox="0 0 32 32" class="w-7 h-7" aria-hidden="true">
                <path fill="currentColor" d="M19.11 17.38c-.27-.14-1.6-.79-1.85-.88c-.25-.09-.43-.14-.62.14c-.18.27-.71.88-.87 1.06c-.16.18-.32.2-.59.07c-.27-.14-1.13-.42-2.15-1.35c-.79-.71-1.32-1.6-1.47-1.87c-.15-.27-.02-.42.12-.56c.12-.12.27-.32.41-.48c.14-.16.18-.27.27-.45c.09-.18.05-.34-.02-.48c-.07-.14-.62-1.49-.85-2.04c-.22-.52-.43-.45-.62-.46c-.16-.01-.34-.01-.53-.01s-.48.07-.73.34c-.25.27-.96.94-.96 2.29s.99 2.65 1.12 2.84c.14.18 1.95 2.98 4.73 4.18c.66.29 1.18.46 1.58.59c.66.21 1.26.18 1.73.11c.53-.08 1.6-.65 1.83-1.27c.23-.62.23-1.16.16-1.27c-.07-.11-.25-.18-.52-.32zM16.02 3.2c-7.09 0-12.82 5.73-12.82 12.82c0 2.26.6 4.39 1.64 6.23L3.2 28.8l6.73-1.65c1.79.98 3.84 1.54 6.09 1.54c7.09 0 12.82-5.73 12.82-12.82S23.11 3.2 16.02 3.2zM16 26.67c-2.14 0-4.12-.62-5.78-1.69l-.41-.26l-4 .98l1.06-3.9l-.27-.4a10.62 10.62 0 0 1-1.64-5.67c0-5.88 4.79-10.67 10.67-10.67S26.67 9.26 26.67 15.14S21.88 26.67 16 26.67z"/>
            </svg>
        </a>

        {{-- carrinho (se quiser mover aqui) --}}
        <button id="miniCartToggle"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-full shadow border bg-white hover:bg-slate-50">
            <span>🛒</span>
            <span>Carrinho</span>
        </button>
    </div>





</footer>
</body>
</html>

<script>
    // some apenas o de sucesso após 4s
    setTimeout(()=>{
        document.querySelectorAll('[role="alert"].border-blue-200')?.forEach(el => el.remove());
    }, 4000);
</script>
