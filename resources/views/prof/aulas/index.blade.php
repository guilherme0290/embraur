@extends('layouts.app')
@section('title','Aulas do Módulo')

@section('content')
    <div class="container-page py-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-semibold">Aulas — {{ $modulo->titulo }}</h1>
                <p class="text-slate-600 text-sm">Curso: {{ $curso->titulo }}</p>
            </div>
            <a href="{{ route('prof.cursos.modulos.index',$curso) }}" class="btn btn-outline">Voltar aos módulos</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-xl border bg-white p-5 shadow-sm">
                @forelse($aulas as $aula)
                    <div class="rounded-lg border mb-4 p-4">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">#{{ $aula->ordem }} — {{ $aula->titulo }}</div>
                            <form method="POST" action="{{ route('prof.cursos.modulos.aulas.update',[$curso,$modulo,$aula]) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf @method('PUT')
                                <input name="titulo" value="{{ $aula->titulo }}" class="h-8 rounded border-slate-300 px-2">
                                <select name="tipo" class="h-8 rounded border-slate-300">
                                    <option value="video" {{ $aula->tipo=='video'?'selected':'' }}>Vídeo</option>
                                    <option value="pdf"   {{ $aula->tipo=='pdf'?'selected':'' }}>PDF</option>
                                    <option value="texto" {{ $aula->tipo=='texto'?'selected':'' }}>Texto</option>
                                </select>
                                <input name="url_video" value="{{ $aula->url_video }}" placeholder="URL do vídeo" class="h-8 rounded border-slate-300 px-2 w-52">
                                <input type="file" name="arquivo" class="h-8">
                                <input type="hidden" name="duracao_minutos" value="{{ $aula->duracao_minutos ?? 0 }}">
                                <label class="text-sm inline-flex items-center gap-2">
                                    <input type="checkbox" name="preview" value="1" {{ $aula->preview ? 'checked':'' }}>
                                    Preview
                                </label>
                                <input type="number" name="ordem" value="{{ $aula->ordem }}" class="h-8 w-16 rounded border-slate-300 px-2">
                                <button class="btn btn-soft h-8">Salvar</button>
                            </form>
                        </div>
                        @if($aula->arquivo_path)
                            <div class="text-xs text-slate-500 mt-2">Arquivo: {{ $aula->arquivo_path }}</div>
                        @endif

                        <form method="POST" action="{{ route('prof.cursos.modulos.aulas.destroy',[$curso,$modulo,$aula]) }}" onsubmit="return confirm('Excluir aula?')" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline h-8">Excluir</button>
                        </form>
                    </div>
                @empty
                    <p class="text-slate-500">Nenhuma aula neste módulo.</p>
                @endforelse
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <h3 class="font-semibold mb-3">Nova aula</h3>
                <form method="POST" action="{{ route('prof.cursos.modulos.aulas.store',[$curso,$modulo]) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input name="titulo" class="w-full h-10 rounded-md border-slate-300" placeholder="Título da aula" required>
                    <select name="tipo" class="w-full h-10 rounded-md border-slate-300">
                        <option value="video">Vídeo</option>
                        <option value="pdf">PDF</option>
                        <option value="texto">Texto</option>
                    </select>
                    <input name="url_video" class="w-full h-10 rounded-md border-slate-300" placeholder="URL do vídeo (se aplicável)">
                    <input type="file" name="arquivo" class="w-full">
                    <input type="hidden" name="duracao_minutos" value="0">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="preview" value="1"> Preview grátis</label>
                    <button class="btn-primary h-10 w-full rounded-md">Adicionar aula</button>
                </form>
            </div>
        </div>
    </div>
@endsection
