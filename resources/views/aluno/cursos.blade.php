@extends('layouts.app')

@section('content')
    <div class="container-page py-6">
        @include('aluno._tabs', ['aluno' => $aluno, 'stats' => [
          'cursos' => count($cursos),
          'concluidos' => collect($cursos)->where('progresso', 100)->count(),
          'horas' => 0,
          'progressoGeral' => (int) round(collect($cursos)->avg('progresso') ?? 0),
        ]])

        <div class="mt-4 rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-lg font-semibold mb-3">Meus Cursos</h3>

            <div class="space-y-3">
                @forelse($cursos as $c)
                    <div class="rounded-lg border p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-200 rounded-md"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ $c['titulo'] }}</div>
                                <div class="text-xs text-gray-500">
                                    Ciclo {{ $c['ciclo'] }} • {{ ucfirst($c['status']) }}
                                    @if(!empty($c['data_vencimento']))
                                        • vence em {{ $c['data_vencimento'] }}
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-2 rounded-full" style="width: {{ $c['progresso'] }}%; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $c['aulas_feitas'] }}/{{ $c['aulas_total'] }} aulas • {{ $c['progresso'] }}%
                                    </div>
                                </div>
                            </div>
                            <a href="{{ $c['link'] }}" class="px-3 py-2 rounded-md bg-green-600 text-white text-xs hover:bg-green-700">
                                Continuar
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Você ainda não tem cursos. Acesse o catálogo e inscreva-se.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
