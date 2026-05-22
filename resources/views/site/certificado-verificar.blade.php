@extends('layouts.app')
@section('title', 'Verificação de Certificado')

{{-- REMOVA este bloco se seu layout já inclui Tailwind --}}
@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    @php
        $fmt = fn($d) => optional($d)->format('d/m/Y') ?? '—';
        $nota = !is_null($cert->nota_aproveitamento)
                ? number_format((float)$cert->nota_aproveitamento, 1, ',', '')
                : '—';
    @endphp

    <section class="w-full bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4">

            <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl">
                <div class="p-6 md:p-8">

                    {{-- Cabeçalho --}}
                    <div class="flex items-center justify-between gap-4">
                        <h1 class="text-lg font-semibold text-gray-900">Verificação de Certificado</h1>
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 text-sm font-medium ring-1 ring-emerald-200">
            ✅ VÁLIDO
          </span>
                    </div>

                    {{-- Código de verificação --}}
                    <div class="mt-6 flex items-end justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Código de verificação</p>
                            <div class="mt-1 font-mono text-xl tracking-wider font-semibold text-gray-900">
                                {{ $cert->codigo_verificacao }}
                            </div>
                        </div>
                        <button
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 active:bg-gray-100"
                            onclick="navigator.clipboard.writeText('{{ $cert->codigo_verificacao }}')">
                            Copiar
                        </button>
                    </div>

                    <div class="my-6 h-px bg-gray-100"></div>

                    {{-- Grid de detalhes --}}
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">Aluno</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ $cert->matricula->aluno->nome_completo ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">Curso</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ $cert->matricula->curso->titulo ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm text-gray-500">Carga horária</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                @php
                                    $minutos = (int) ($cert->matricula->curso->carga_horaria_total ?? 0);
                                    $horas = $minutos / 60;
                                    $horasFmt = $minutos > 0
                                        ? (fmod($horas, 1.0) === 0.0
                                            ? number_format($horas, 0, ',', '.')
                                            : number_format($horas, 1, ',', '.'))
                                        : '—';
                                @endphp
                                {{ $horasFmt }}@if($horasFmt !== '—')h@endif
                            </dd>
                        </div>

{{--                        <div>--}}
{{--                            <dt class="text-sm text-gray-500">Período</dt>--}}
{{--                            <dd class="mt-1 font-medium text-gray-900">--}}
{{--                                De {{ $fmt($cert->matricula->data_inicio ?? null) }}--}}
{{--                                a {{ $fmt($cert->matricula->data_fim ?? null) }}--}}
{{--                            </dd>--}}
{{--                        </div>--}}

{{--                        <div>--}}
{{--                            <dt class="text-sm text-gray-500">Nota de aproveitamento</dt>--}}
{{--                            <dd class="mt-1 font-medium text-gray-900">{{ $nota }}</dd>--}}
{{--                        </div>--}}

                        <div>
                            <dt class="text-sm text-gray-500">Emitido em</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ $fmt($cert->data_emissao ?? null) }}
                            </dd>
                        </div>
                    </dl>

                    {{-- Link da verificação --}}
                    <div class="mt-8 border-t border-gray-100 pt-4 text-sm text-gray-600">
                        Para confirmar a autenticidade, compare o código acima com o impresso no certificado,
                        ou acesse:
                        <a href="{{ route('certificados.verify', $cert->codigo_verificacao) }}"
                           class="text-indigo-600 hover:text-indigo-700 underline break-all">
                            {{ route('certificados.verify', $cert->codigo_verificacao) }}
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </section>
@endsection
