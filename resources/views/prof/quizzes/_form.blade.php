@php
    $editing   = (bool) ($quiz ?? null);
    $cursoSel  = old('curso_id', $quiz->curso_id ?? request('curso'));
    $moduloSel = old('modulo_id', $quiz->modulo_id ?? request('modulo'));
    $escopoSel = old('escopo',    $quiz->escopo    ?? 'curso');

    // Normaliza questões para o include
    $questoes = collect(old('questoes', $quiz
        ? $quiz->questoes->map(function($q){
            return [
                'id'        => $q->id,
                'enunciado' => $q->enunciado,
                'tipo'      => $q->tipo,
                'pontuacao' => $q->pontuacao,
                'opcoes'    => $q->opcoes->map(fn($o)=>[
                    'id' => $o->id,
                    'texto' => $o->texto,
                    'correta' => $o->correta ? 1 : 0,
                ])->toArray(),
            ];
        })->toArray()
        : []
    ));
@endphp

{{-- CAMPOS PRINCIPAIS --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div>
        <label class="text-sm font-medium">Escopo *</label>
        <select name="escopo" class="mt-1 w-full h-10 rounded-md border px-3">
            <option value="curso"  @selected($escopoSel==='curso')>Curso</option>
            <option value="modulo" @selected($escopoSel==='modulo')>Módulo</option>
        </select>
    </div>

    <div>
        <label class="text-sm font-medium">Curso *</label>
        <select name="curso_id" id="selCurso" class="mt-1 w-full h-10 rounded-md border px-3" required>
            <option value="">— Selecione —</option>
            @foreach($cursos as $c)
                <option value="{{ $c->id }}" @selected($cursoSel == $c->id)>{{ $c->titulo }}</option>
            @endforeach
        </select>
        @error('curso_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="text-sm font-medium">Módulo (se escopo = módulo)</label>
        <select name="modulo_id" id="selModulo" class="mt-1 w-full h-10 rounded-md border px-3">
            <option value="">— Opcional —</option>
            @foreach(($modulosPorCurso[$cursoSel] ?? []) as $m)
                <option value="{{ $m->id }}" @selected($moduloSel == $m->id)>{{ $m->titulo }}</option>
            @endforeach
        </select>
        @error('modulo_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

</div>

{{-- QUESTÕES (UM ÚNICO include) --}}
@include('prof.quizzes._questoes', ['questoes' => $questoes])

<div class="mt-6 flex justify-end gap-2">
    <a href="{{ url()->previous() }}" class="btn btn-outline">Cancelar</a>
    <button class="btn btn-primary">{{ $editing ? 'Salvar alterações' : 'Criar Quiz' }}</button>
</div>

{{-- Curso -> Módulo --}}
<script>
    (function(){
        const selCurso  = document.getElementById('selCurso');
        const selModulo = document.getElementById('selModulo');
        const mapa = @json(collect($modulosPorCurso)->map(fn($c)=>$c->map(fn($m)=>['id'=>$m->id,'titulo'=>$m->titulo])));

        function refresh(){
            if(!selCurso || !selModulo) return;
            const cid = selCurso.value;
            const lista = mapa[cid] || [];
            const atual = "{{ $moduloSel }}";
            selModulo.innerHTML = '<option value="">— Opcional —</option>';
            lista.forEach(m=>{
                const o = document.createElement('option');
                o.value = m.id; o.textContent = m.titulo;
                if (String(m.id) === String(atual)) o.selected = true;
                selModulo.appendChild(o);
            });
        }

        selCurso?.addEventListener('change', refresh);
        // na edição pode precisar repopular:
        if (selCurso && selCurso.value) refresh();
    })();
</script>
