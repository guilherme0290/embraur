{{--@extends('layouts.app')--}}
{{--@section('title','Criar Novo Curso')--}}
{{--@section('content')--}}
<section class="container-page mx-auto py-6 max-w-5xl">
    @php
        $modulosFromOld = old('modulos');
        $modulosForm = is_array($modulosFromOld)
            ? $modulosFromOld
            : ($curso->modulos ?? collect());
        $quizzesPorModulo = ($curso->modulos ?? collect())
            ->filter(fn($modulo) => data_get($modulo, 'id'))
            ->mapWithKeys(fn($modulo) => [data_get($modulo, 'id') => data_get($modulo, 'quiz')]);
        $cargaHorariaHoras = old('carga_horaria_horas');
        if ($cargaHorariaHoras === null) {
            $minutosCurso = (int)($curso->carga_horaria_total ?? 0);
            $cargaHorariaHoras = $minutosCurso > 0 ? rtrim(rtrim(number_format($minutosCurso / 60, 2, '.', ''), '0'), '.') : '';
        }
    @endphp

    {{-- Estilos leves para separar módulos/aulas sem quebrar Tailwind --}}
    <style>
        /* módulo: moldura mais sutil + sombra leve */
        #modulosWrap [data-modulo] { border-radius: 0.75rem; }
        /* aula: faixa lateral + separação por linhas finas e zebra */
        .aula-card {
            position: relative;
            border-left-width: 4px;        /* faixa lateral */
            border-left-color: rgb(59,130,246); /* blue-500 */
        }
        /* zebra: usa :nth-child(odd/even) dentro do bloco de aulas */
        [data-aulas] > .aula-card:nth-child(odd)  { background: #f8fafc; } /* slate-50 */
        [data-aulas] > .aula-card:nth-child(even) { background: #ffffff; }
        /* linhas finas no topo/rodapé de cada aula */
        .aula-card::before, .aula-card::after{
            content: "";
            position: absolute;
            left: 0; right: 0;
            height: 1px;
            background: rgba(148,163,184,.35); /* slate-400/35 */
        }
        .aula-card::before{ top: -8px; }
        .aula-card::after { bottom: -8px; }
        /* pílulas/badges */
        .pill { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .5rem; border-radius:9999px; font-size:.72rem; font-weight:600; }
    </style>

    {{-- NAV de seção (fixo no topo) --}}
    <nav class="sticky top-0 z-20 -mx-4 mb-4 bg-white/80 backdrop-blur border-b">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm">
                <a href="#sec-basicas" class="px-3 py-1 rounded-full border hover:bg-slate-50">1. Informações</a>
                @if(($mode ?? 'create') === 'edit')
                    <a href="#sec-estrutura" class="px-3 py-1 rounded-full border hover:bg-slate-50">2. Estrutura</a>
                @endif
            </div>


            <div class="hidden md:flex items-center gap-2">
                <button type="submit" form="cursoForm" name="salvar" value="rascunho" class="btn btn-outline h-9">
                    Salvar como Rascunho
                </button>
                <button type="submit" form="cursoForm" name="salvar" value="publicar" class="btn btn-primary h-9">
                    {{ ($mode ?? 'create') === 'edit' ? 'Salvar Alterações' : 'Criar Curso' }}
                </button>
            </div>

        </div>
    </nav>

    {{-- Cabeçalho --}}
    <div class="mb-4 flex items-center justify-between px-1">
        <a href="{{ route('prof.cursos.index') }}" class="btn btn-outline">← Voltar</a>
        <div class="flex gap-2 md:hidden">
            <button type="submit" name="salvar" value="rascunho" class="btn btn-outline">Rascunho</button>
            <button type="submit" name="salvar" value="publicar" class="btn btn-primary">
                {{ ($mode ?? 'create') === 'edit' ? 'Salvar' : 'Criar' }}
            </button>
        </div>
    </div>

    {{-- Card Informações Básicas --}}
    <div id="sec-basicas" class="rounded-xl border bg-white p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">🗂️ Informações Básicas</h2>
            <span class="text-xs text-slate-500">Preencha os dados principais do curso</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Título do Curso *</label>
                <input name="titulo"
                       value="{{ old('titulo', $curso->titulo) }}"
                       data-required-field="1" data-label="Título do curso"
                       required placeholder="Ex.: Curso completo de React"
                       class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                @error('titulo') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="text-sm font-medium">Categoria *</label>
                <select name="categoria_id" required
                        data-required-field="1" data-label="Categoria"
                        class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 bg-white focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                    <option value="">Selecione uma categoria</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" @selected(old('categoria_id', $curso->categoria_id) == $cat->id)>
                            {{ $cat->nome }}
                        </option>
                    @endforeach
                </select>
                @error('categoria_id') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            {{--
            <div class="md:col-span-2">
                <label class="text-sm font-medium">Descrição Curta</label>
                <textarea
                    name="descricao"
                    id="descricao"
                    class="js-ckeditor mt-1 w-full rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                    rows="4"
                    placeholder="Escreva um resumo do curso "
                >{{ old('descricao', $curso->descricao) }}</textarea>
            </div>
            --}}



            <div class="md:col-span-2">
                <label class="text-sm font-medium">Descrição Completa</label>
                <textarea
                    name="descricao_completa"
                    id="descricao_completa"
                    data-required-field="1" data-label="Descrição completa"
                    class="js-ckeditor mt-1 w-full rounded-md border border-slate-300"
                    placeholder="Descreva detalhadamente o que os alunos irão aprender..."
                    rows="8"
                >{{ old('descricao_completa', $curso->descricao_completa) }}</textarea>
                @error('descricao_completa') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="text-sm font-medium">Nível *</label>
                <select name="nivel" required
                        data-required-field="1" data-label="Nível"
                        class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 bg-white">
                    @php
                        // usa o que veio do POST (old) ou o que está no modelo
                        $nivelSel = old('nivel', $curso->nivel ?? 'todos');
                    @endphp
                    <option value="todos"          @selected($nivelSel==='todos')>Todos os Níveis</option>
                    <option value="iniciante"      @selected($nivelSel==='iniciante')>Iniciante</option>
                    <option value="intermediario"  @selected($nivelSel==='intermediario')>Intermediário</option>
                    <option value="avancado"       @selected($nivelSel==='avancado')>Avançado</option>
                </select>
                @error('nivel') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="text-sm font-medium">Carga horária do curso (horas)</label>
                <input name="carga_horaria_horas"
                       value="{{ $cargaHorariaHoras }}"
                       required
                       data-required-field="1" data-label="Carga horária do curso"
                       type="number" min="0.1" step="0.1" placeholder="Ex.: 40"
                       class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                @error('carga_horaria_horas') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
            <label class="text-sm font-medium">Preço original (R$)</label>
            <input name="preco_original"
                   value="{{ old('preco_original', $curso->preco_original) }}"
                   id="precoOriginalInput"
                   required
                   data-required-field="1" data-label="Preço original"
                   data-money-mask="true"
                   inputmode="decimal"
                   autocomplete="off"
                   type="text" placeholder="Ex.: 99,90"
                   class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
            @error('preco_original') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
            <div>

                <label class="text-sm font-medium">Preço (R$)</label>
                <input name="preco"
                       value="{{ old('preco', $curso->preco) }}"
                       id="precoInput"
                       required
                       data-required-field="1" data-label="Preço"
                       data-money-mask="true"
                       inputmode="decimal"
                       autocomplete="off"
                       type="text" placeholder="Ex.: 99,90"
                       class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                @error('preco') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror



            </div>

            <div>
                <label class="text-sm font-medium">Nota Minima Para Aprovação</label>
                <input name="nota_minima_aprovacao"
                       value="{{ old('nota_minima_aprovacao', $curso->nota_minima_aprovacao) }}"
                       required
                       data-required-field="1" data-label="Nota mínima para aprovação"
                       type="number" min="0" max="10" step="0.1" placeholder="Ex.: 7"
                       class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                @error('nota_minima_aprovacao') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="text-sm font-medium">Imagem do Curso</label>
                <div class="mt-1 rounded-md border border-dashed p-6 text-center text-slate-500 bg-slate-50/50">
                    <div class="mb-3">Clique para fazer upload ou arraste uma imagem</div>
                    <input type="file" name="imagem_capa" id="imagemCapa" accept="image/*"
                           @if(($mode ?? 'create') === 'create') required data-required-field="1" data-label="Imagem do curso" @endif
                           class="mx-auto block">
                    <div class="mt-3 aspect-video rounded bg-slate-100 overflow-hidden ring-1 ring-slate-200">
                        <img id="previewCapa"
                             src="{{ $curso->imagem_capa_url ?? '' }}"
                             class="w-full h-full object-cover {{ $curso->imagem_capa_url ? '' : 'hidden' }}">
                    </div>
                    @error('imagem_capa') <div class="text-red-600 text-xs mt-2">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Card Estrutura do Curso --}}
    @if(($mode ?? 'create') === 'edit')
    <div id="sec-estrutura" class="rounded-xl border bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">📚 Estrutura do Curso</h2>
            <div class="flex items-center gap-2">
                <button type="button" id="btnExpandAll" class="text-sm px-3 py-1 rounded border hover:bg-slate-50">Expandir tudo</button>
                <button type="button" id="btnCollapseAll" class="text-sm px-3 py-1 rounded border hover:bg-slate-50">Recolher tudo</button>
            </div>
        </div>

        <div id="modulosWrap" class="space-y-4">
            @foreach($modulosForm as $mIdx => $modulo)
                @php
                    $moduloId = data_get($modulo, 'id');
                    $moduloTitulo = data_get($modulo, 'titulo');
                    $moduloDescricao = data_get($modulo, 'descricao');
                    $aulasModulo = data_get($modulo, 'aulas', []);
                    $quizModulo = data_get($modulo, 'quiz') ?: $quizzesPorModulo->get($moduloId);
                @endphp
                <div class="rounded-lg border p-0 overflow-hidden" data-modulo="{{ $mIdx }}" data-id="{{ $moduloId }}" data-reorder-url="{{ isset($curso->id) ? route('prof.cursos.modulos.reorder', $curso->id) : '' }}">
                    {{-- Cabeçalho do módulo (colapsável) --}}
                    <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b">
                        <div class="flex items-center gap-3">
                            <button type="button" class="text-xs px-2 py-1 rounded border bg-white cursor-grab" data-drag-handle draggable="true">Arrastar</button>
                            <button type="button" class="toggle-modulo h-8 w-8 rounded-md border bg-white hover:bg-slate-100 grid place-items-center"
                                    aria-expanded="true">
                                <span class="i">▾</span>
                            </button>
                            <div>
                                <h3 class="font-semibold">Módulo <span class="mod-num">{{ $mIdx + 1 }}</span></h3>
                                {{-- Badge de status da Prova --}}
                                <div class="mt-1">
                                    @if($quizModulo)
                                        <span class="pill bg-green-100 text-green-700 border border-green-200">
                                                ✅ Prova cadastrada
                                            </span>
                                        @if(isset($curso->id) && $quizModulo)
                                            <a href="{{ route('prof.quizzes.edit', $quizModulo->id ?? 0) }}"
                                               class="text-xs underline text-green-700 ml-2">Editar</a>
                                            <button type="button"
                                                    class="text-xs underline text-red-700 ml-2"
                                                    data-action="delete-quiz"
                                                    data-url="{{ route('prof.quizzes.destroy', $quizModulo->id) }}">
                                                Excluir
                                            </button>
                                        @endif
                                    @else
                                        <span class="pill bg-slate-100 text-slate-700 border border-slate-200">
                                                ⏳ Sem prova
                                            </span>
                                        @if(isset($curso->id) && $moduloId)
                                            <a href="{{ route('prof.quizzes.create', ['curso' => $curso->id, 'modulo' => $moduloId]) }}"
                                               class="text-xs underline text-blue-700 ml-2">Criar agora</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="text-xs underline" data-action="move-modulo-up">Subir</button>
                            <button type="button" class="text-xs underline" data-action="move-modulo-down">Descer</button>
                            <button type="button" class="text-red-600 hover:underline"
                                    onclick="window.removeModulo(this)">Remover</button>
                        </div>
                    </div>

                    <div class="modulo-body p-4">
                        {{-- ID do módulo (update) --}}
                        <input type="hidden" name="modulos[{{ $mIdx }}][id]" value="{{ $moduloId }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium">Título do Módulo</label>
                                <input required name="modulos[{{ $mIdx }}][titulo]"
                                       value="{{ old("modulos.$mIdx.titulo", $moduloTitulo) }}"
                                       data-required-field="1" data-label="Título do módulo"
                                       class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                                @error("modulos.$mIdx.titulo") <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium">Descrição do Módulo</label>
                                <textarea name="modulos[{{ $mIdx }}][descricao]"
                                          class="js-ckeditor mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                                          rows="4"
                                >{{ old("modulos.$mIdx.descricao", $moduloDescricao) }}</textarea>
                            </div>
                        </div>

                        {{-- Aulas --}}
                        <div class="space-y-6" data-aulas="{{ $mIdx }}" data-reorder-url="{{ ($moduloId && isset($curso->id)) ? route('prof.cursos.modulos.aulas.reorder', [$curso->id, $moduloId]) : '' }}">
                            @foreach($aulasModulo as $aIdx => $aula)
                                <div class="aula-card grid grid-cols-1 md:grid-cols-4 gap-3 border rounded-md p-3 bg-white" data-aula="{{ $aIdx }}" data-id="{{ data_get($aula, 'id') }}">
                                    <input type="hidden" name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][id]" value="{{ data_get($aula, 'id') }}">

                                    <div class="md:col-span-4 flex justify-end">
                                        <button type="button" class="text-xs px-2 py-1 rounded border bg-white cursor-grab" data-drag-handle draggable="true">Arrastar aula</button>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block h-5 leading-5 text-sm font-medium whitespace-nowrap">Título da Aula</label>
                                        <input name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][titulo]"
                                               value="{{ old("modulos.$mIdx.aulas.$aIdx.titulo", data_get($aula, 'titulo')) }}"
                                               data-required-field="1" data-label="Título da aula"
                                               class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200" placeholder="Ex: Criando componentes">
                                        @error("modulos.$mIdx.aulas.$aIdx.titulo") <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <input type="hidden"
                                           name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][duracao_minutos]"
                                           value="{{ old("modulos.$mIdx.aulas.$aIdx.duracao_minutos", data_get($aula, 'duracao_minutos', 0)) }}">

                                    <div>
                                        <label class="block h-5 leading-5 text-sm font-medium text-center">Tipo</label>
                                        @php $tipoSel = old("modulos.$mIdx.aulas.$aIdx.tipo", data_get($aula, 'tipo')); @endphp
                                        <select name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][tipo]"
                                                data-required-field="1" data-label="Tipo da aula"
                                                class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 bg-white focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                                            <option value="">Selecione o tipo</option>
                                            <option value="video"   @selected($tipoSel==='video')>Vídeo</option>
                                            <option value="texto"   @selected($tipoSel==='texto')>Texto</option>

                                            <option value="arquivo" @selected($tipoSel==='arquivo')>Arquivo</option>
                                        </select>
                                        @error("modulos.$mIdx.aulas.$aIdx.tipo") <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="md:col-span-4">
                                        <label class="text-sm font-medium">Descrição da Aula (opcional)</label>
                                        <textarea
                                            id="editor-desc-{{ $mIdx }}-{{ $aIdx }}"
                                            name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][conteudo_texto]"
                                            class="js-ckeditor mt-1 w-full rounded-md border border-slate-300"
                                            rows="5"
                                        >{{ old("modulos.$mIdx.aulas.$aIdx.descricao", data_get($aula, 'conteudo_texto')) }}</textarea>



                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="text-sm font-medium">URL de Conteúdo (opcional)()</label>
                                        <input name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][conteudo_url]"
                                               value="{{ old("modulos.$mIdx.aulas.$aIdx.conteudo_url", data_get($aula, 'conteudo_url')) }}"
                                               class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200" placeholder="https://...">
                                    </div>
                                    {{-- NOVO: upload de vídeo opcional --}}
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-medium">Enviar Vídeo (opcional)</label>
                                        <input type="file"
                                               name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][video_file]"
                                               accept="video/*"
                                               class="mt-1 block w-full text-sm file:mr-3 file:py-2 file:px-3 file:rounded-md file:border file:bg-slate-50 file:hover:bg-slate-100">
                                        <p class="text-xs text-slate-500 mt-1">
                                            Se você enviar um arquivo, a URL será ignorada. Formatos: MP4/WebM/OGG.
                                        </p>
                                    </div>

                                    @if($aIdx > 0)
                                        <div class="flex items-center gap-2">
                                            @php
                                                $lib = old("modulos.$mIdx.aulas.$aIdx.liberada_apos_anterior", data_get($aula, 'liberada_apos_anterior') ? '1' : null);
                                            @endphp
                                            <input type="checkbox"
                                                   name="modulos[{{ $mIdx }}][aulas][{{ $aIdx }}][liberada_apos_anterior]"
                                                   value="1" @checked($lib == '1')
                                                   class="h-4 w-4 border border-slate-300">
                                            <label class="text-sm">Liberar só após concluir aula anterior</label>
                                        </div>
                                    @endif

                                    <div class="md:col-span-4 text-right">
                                        <button type="button" class="text-xs underline mr-2" data-action="move-aula-up">Subir</button>
                                        <button type="button" class="text-xs underline mr-2" data-action="move-aula-down">Descer</button>
                                        <button type="button" class="text-red-600 hover:underline" data-action="remove-aula">Remover aula</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Ações do módulo: adicionar aula + criar prova --}}
                        <div class="mt-4 flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn btn-outline" data-action="add-aula">＋ Adicionar Aula</button>

                                @if(isset($curso->id) && $moduloId && !$quizModulo)
                                    <a
                                        href="{{ route('prof.quizzes.create', ['curso' => $curso->id, 'modulo' => $moduloId]) }}"
                                        class="btn btn-soft"
                                        title="Criar prova para este módulo"
                                    >
                                        ✏️ Criar Prova do Módulo
                                    </a>
                                @endif
                            </div>

                            <span class="text-xs text-slate-500">Organize as aulas e cadastre a prova do módulo quando estiver pronto</span>
                        </div>
                        @if(isset($curso->id) && $moduloId && isset($cursosDoProfessor))
                            <div class="mt-3 flex items-center justify-end gap-2">
                                <select class="h-9 rounded-md border px-2 text-sm" data-copy-module-destino>
                                    <option value="">Copiar módulo para...</option>
                                    @foreach($cursosDoProfessor as $cursoDestino)
                                        <option value="{{ $cursoDestino->id }}">{{ $cursoDestino->titulo }}</option>
                                    @endforeach
                                </select>
                                <button type="button"
                                        class="text-sm px-3 py-1 rounded border hover:bg-slate-50"
                                        data-action="copy-module"
                                        data-url="{{ route('prof.cursos.modulos.copy', [$curso->id, $moduloId]) }}">
                                    Copiar módulo
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between">
            <button type="button" class="btn btn-outline" id="addModuloBtn">＋ Adicionar Módulo</button>
            <span class="text-xs text-slate-500">Use os botões acima para organizar os módulos</span>
        </div>
    </div>
    @endif

    {{-- Barra de ações fixa no rodapé --}}
    <div class="sticky bottom-0 z-20 mt-6 bg-white/80 backdrop-blur border-t">
        <div class="max-w-5xl mx-auto px-1 py-3 flex justify-end gap-2">
            @if(($mode ?? 'create') === 'edit' && isset($curso->id))
                <a href="{{ route('prof.cursos.certificado.preview', $curso->id) }}"
                   target="_blank"
                   rel="noopener"
                   class="btn btn-outline h-9">
                    Pré-visualizar certificado
                </a>
            @endif
            <button type="submit" form="cursoForm" name="salvar" value="rascunho" class="btn btn-outline h-9">
                Salvar como Rascunho
            </button>
            <button type="submit" form="cursoForm" name="salvar" value="publicar" class="btn btn-primary h-9">
                {{ ($mode ?? 'create') === 'edit' ? 'Salvar Alterações' : 'Criar Curso' }}
            </button>
        </div>
    </div>

</section>

{{-- JS: preview, colapsar módulos, numerar e atalhos (sem mudanças de seletor) --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    (() => {
        /* =========================================================
         *  CKEDITOR: inicializador idempotente (reutilizável)
         * =======================================================*/
        const UPLOAD_URL = "{{ route('prof.uploads.ckeditor') }}?_token={{ csrf_token() }}";

        const htmlSupport = {
            allow: [{ name: /^(video|source)$/, attributes: true, classes: true, styles: true }]
        };

        const mediaEmbed = {
            previewsInData: true,
            extraProviders: [
                {
                    name: 'localVideo',
                    url: /^https?:\/\/[^ ]+\.(mp4|webm|ogg)$/i,
                    html: match => {
                        const url = match[0];
                        const ext = (url.split('.').pop() || '').toLowerCase();
                        const type = ext === 'ogv' ? 'ogg' : ext;
                        return `<video controls style="max-width:100%;height:auto;"><source src="${url}" type="video/${type}"></video>`;
                    }
                }
            ]
        };

        const toolbar = [
            'undo','redo','|',
            'heading','|',
            'bold','italic','underline','link','|',
            'bulletedList','numberedList','blockQuote','|',
            'insertTable','imageUpload','mediaEmbed','|',
            'alignment','outdent','indent','|',
            'codeBlock','horizontalLine'
        ];

        function initCKEditorsIn(root = document) {
            root.querySelectorAll('textarea.js-ckeditor').forEach((el) => {
                // bloqueios: já pronto OU pendente de inicialização
                if (el.hasAttribute('data-cke-ready') || el.hasAttribute('data-cke-pending')) return;

                // marca como pendente ANTES de chamar o create()
                el.setAttribute('data-cke-pending','1');

                ClassicEditor.create(el, {
                    language: 'pt-br',
                    toolbar: { items: toolbar },
                    ckfinder: { uploadUrl: UPLOAD_URL },
                    mediaEmbed,
                    htmlSupport,
                    removePlugins: ['CKBox','CKFinder','EasyImage']
                })
                    .then((editor) => {
                        // guarda a instância para poder destruir depois
                        el._ckeditor = editor;

                        el.removeAttribute('data-cke-pending');
                        el.setAttribute('data-cke-ready','1');

                        // auto-mediaEmbed para vídeos enviados
                        const fileRepo = editor.plugins.get('FileRepository');
                        const origCreateAdapter = fileRepo.createUploadAdapter.bind(fileRepo);
                        fileRepo.createUploadAdapter = loader => {
                            const adapter = origCreateAdapter(loader);
                            const origUpload = adapter.upload?.bind(adapter);
                            if (!origUpload) return adapter;
                            adapter.upload = async () => {
                                const res = await origUpload();
                                try {
                                    const url = res?.default ?? res?.url ?? res?.urls?.default ?? res?.url;
                                    if (url && /\.(mp4|webm|ogg)$/i.test(url)) {
                                        editor.execute('mediaEmbed', url);
                                        return { default: url };
                                    }
                                } catch(e) {}
                                return res;
                            };
                            return adapter;
                        };
                    })
                    .catch((e) => {
                        el.removeAttribute('data-cke-pending');
                        console.error(e);
                    });
            });
        }

        // Exponha globalmente para uso após inserções dinâmicas
        window.initCKEditorsIn = initCKEditorsIn;

        // Inicializa os que já estão na página
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => initCKEditorsIn(document));
        } else {
            initCKEditorsIn(document);
        }

        // (Opcional) Observa novos nós e inicializa automaticamente
        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                for (const node of m.addedNodes) {
                    if (!(node instanceof Element)) continue;
                    if (node.matches?.('textarea.js-ckeditor') || node.querySelector?.('textarea.js-ckeditor')) {
                        initCKEditorsIn(node);
                    }
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });

        /* =========================================================
         *  UI: preview imagem + módulos/aulas dinâmicos
         * =======================================================*/
        // Preview imagem de capa
        const imgInput = document.getElementById('imagemCapa');
        if (imgInput) {
            imgInput.addEventListener('change', e => {
                const f = e.target.files?.[0]; if (!f) return;
                const img = document.getElementById('previewCapa');
                img.src = URL.createObjectURL(f);
                img.onload = ()=> URL.revokeObjectURL(img.src);
                img.classList.remove('hidden');
            });
        }

        // Máscara monetária (pt-BR) para preço/preço original.
        function formatMoneyBr(raw) {
            const digits = String(raw || '').replace(/\D/g, '');
            if (!digits) return '';
            const cents = parseInt(digits, 10);
            return (cents / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function parseMoneyBrToBackend(value) {
            if (!value) return '';
            const normalized = String(value)
                .replace(/\./g, '')
                .replace(',', '.')
                .replace(/[^\d.]/g, '');
            return normalized;
        }

        function bindMoneyMask(input) {
            if (!input || input.dataset.moneyBound === '1') return;
            input.dataset.moneyBound = '1';

            input.value = formatMoneyBr(input.value);

            input.addEventListener('input', () => {
                input.value = formatMoneyBr(input.value);
            });

            input.addEventListener('blur', () => {
                input.value = formatMoneyBr(input.value);
            });
        }

        const moneyInputs = document.querySelectorAll('input[data-money-mask="true"]');
        moneyInputs.forEach(bindMoneyMask);

        const form = document.getElementById('cursoForm') || moneyInputs[0]?.closest('form');

        function clearInlineErrors() {
            document.querySelectorAll('[data-inline-error="1"]').forEach((el) => el.remove());
            document.querySelectorAll('[data-structure-error="1"]').forEach((el) => el.remove());
            document.querySelectorAll('[data-required-field="1"]').forEach((field) => {
                field.classList.remove('border-red-500');
            });
        }

        function addInlineError(field, message) {
            const error = document.createElement('div');
            error.className = 'text-red-600 text-xs mt-1';
            error.dataset.inlineError = '1';
            error.textContent = message;
            field.classList.add('border-red-500');
            field.insertAdjacentElement('afterend', error);
        }

        function getFieldValue(field) {
            if (field._ckeditor) {
                const html = field._ckeditor.getData() || '';
                const text = html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
                return text;
            }
            return String(field.value || '').trim();
        }

        const modWrap = document.getElementById('modulosWrap');
        const addModuloBtn = document.getElementById('addModuloBtn');

        function validateRequiredFields() {
            clearInlineErrors();
            let firstInvalid = null;
            const modules = modWrap ? Array.from(modWrap.querySelectorAll('[data-modulo]')) : [];

            if (modWrap && modules.length === 0) {
                const err = document.createElement('div');
                err.className = 'text-red-600 text-xs mt-2';
                err.dataset.structureError = '1';
                err.textContent = 'Adicione pelo menos 1 módulo para continuar.';
                modWrap.insertAdjacentElement('afterend', err);
                modWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            for (const moduleCard of modules) {
                const aulas = moduleCard.querySelectorAll('[data-aula]');
                if (aulas.length === 0) {
                    const err = document.createElement('div');
                    err.className = 'text-red-600 text-xs mt-2';
                    err.dataset.structureError = '1';
                    err.textContent = 'Cada módulo precisa ter pelo menos 1 aula.';
                    moduleCard.querySelector('.modulo-body')?.appendChild(err);
                    setExpanded(moduleCard, true);
                    moduleCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }

            const requiredFields = Array.from(document.querySelectorAll('[data-required-field="1"]'))
                .filter((field) => field.offsetParent !== null);

            requiredFields.forEach((field) => {
                const value = getFieldValue(field);
                if (!value) {
                    const label = field.dataset.label || 'Campo obrigatório';
                    addInlineError(field, `${label} é obrigatório.`);
                    if (!firstInvalid) firstInvalid = field;
                    return;
                }

                if (field.type === 'number' && field.min !== '' && !Number.isNaN(Number(value))) {
                    if (Number(value) < Number(field.min)) {
                        const label = field.dataset.label || 'Campo obrigatório';
                        addInlineError(field, `${label} deve ser maior ou igual a ${field.min}.`);
                        if (!firstInvalid) firstInvalid = field;
                    }
                }
            });

            if (firstInvalid) {
                const moduloCard = firstInvalid.closest('[data-modulo]');
                if (moduloCard) setExpanded(moduloCard, true);
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
                return false;
            }

            return true;
        }

        form?.addEventListener('submit', (e) => {
            renumberStructureNames();

            if (!validateRequiredFields()) {
                e.preventDefault();
                return;
            }

            moneyInputs.forEach((input) => {
                input.value = parseMoneyBrToBackend(input.value);
            });
        });

        if (!modWrap) return;

        function submitDynamicForm(url, fields = {}, method = 'POST'){
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = url;
            f.style.display = 'none';

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = '{{ csrf_token() }}';
            f.appendChild(token);

            if (method !== 'POST') {
                const spoof = document.createElement('input');
                spoof.type = 'hidden';
                spoof.name = '_method';
                spoof.value = method;
                f.appendChild(spoof);
            }

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                f.appendChild(input);
            });

            document.body.appendChild(f);
            f.submit();
        }

        async function postJson(url, payload){
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                throw new Error(`Falha ao salvar ordenação (${res.status})`);
            }

            return res.json();
        }

        function renumberModules(){
            modWrap.querySelectorAll('[data-modulo]').forEach((el, i)=>{
                const num = el.querySelector('.mod-num');
                if (num) num.textContent = (i+1);
            });
        }

        function renumberStructureNames(){
            if (!modWrap) return;

            modWrap.querySelectorAll('[data-modulo]').forEach((moduleCard, mIdx) => {
                moduleCard.dataset.modulo = mIdx;
                const num = moduleCard.querySelector('.mod-num');
                if (num) num.textContent = mIdx + 1;

                moduleCard.querySelectorAll('[name^="modulos["]').forEach((field) => {
                    field.name = field.name.replace(/^modulos\[\d+\]/, `modulos[${mIdx}]`);
                });

                moduleCard.querySelectorAll('[data-aula]').forEach((aulaCard, aIdx) => {
                    aulaCard.dataset.aula = aIdx;
                    aulaCard.querySelectorAll('[name^="modulos["]').forEach((field) => {
                        field.name = field.name.replace(
                            /^modulos\[(\d+)\]\[aulas\]\[\d+\]/,
                            `modulos[${mIdx}][aulas][${aIdx}]`
                        );
                    });
                });
            });
        }

        function moveElement(el, direction){
            if (!el) return;
            const target = direction < 0 ? el.previousElementSibling : el.nextElementSibling;
            if (!target) return;
            if (direction < 0) {
                target.insertAdjacentElement('beforebegin', el);
            } else {
                target.insertAdjacentElement('afterend', el);
            }
            renumberStructureNames();
        }

        function directItems(container, selector){
            return Array.from(container.children).filter((el) => el.matches(selector));
        }

        function reorderDomByIds(container, selector, ids){
            const byId = new Map(directItems(container, selector).map((el) => [String(el.dataset.id), el]));
            ids.forEach((id) => {
                const el = byId.get(String(id));
                if (el) container.appendChild(el);
            });
            renumberStructureNames();
        }

        function savedReorderPayload(container, selector){
            const items = directItems(container, selector);
            if (items.length === 0 || items.some((el) => !el.dataset.id)) return null;
            return items.map((el, idx) => ({ id: Number(el.dataset.id), ordem: idx + 1 }));
        }

        async function persistContainerOrder(container, selector, url){
            const ordens = savedReorderPayload(container, selector);
            if (!url || !ordens) return false;

            await postJson(url, { ordens });
            reorderDomByIds(container, selector, ordens.map((item) => item.id));
            return true;
        }

        async function applySavedOrder(container, selector, url, orderedElements){
            if (!url || orderedElements.some((el) => !el.dataset.id)) return false;

            const ordens = orderedElements.map((el, idx) => ({ id: Number(el.dataset.id), ordem: idx + 1 }));
            await postJson(url, { ordens });
            reorderDomByIds(container, selector, ordens.map((item) => item.id));
            return true;
        }

        async function moveElementPersisted(el, direction, selector, container, url){
            if (!el || !container) return;
            const items = directItems(container, selector);
            const from = items.indexOf(el);
            const to = from + direction;
            if (from < 0 || to < 0 || to >= items.length) return;

            const ordered = [...items];
            ordered.splice(from, 1);
            ordered.splice(to, 0, el);

            try {
                const persisted = await applySavedOrder(container, selector, url, ordered);
                if (!persisted) {
                    moveElement(el, direction);
                }
            } catch (err) {
                alert(err.message || 'Não foi possível salvar a ordenação.');
            }
        }

        function bindDragDrop(container, selector, urlResolver){
            let dragged = null;

            container.addEventListener('dragstart', (e) => {
                const handle = e.target.closest('[data-drag-handle]');
                if (!handle) return;
                const item = handle.closest(selector);
                if (!item || !container.contains(item)) return;
                dragged = item;
                item.classList.add('opacity-60');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', item.dataset.id || '');
            });

            container.addEventListener('dragend', () => {
                dragged?.classList.remove('opacity-60');
                dragged = null;
            });

            container.addEventListener('dragover', (e) => {
                if (!dragged) return;
                const target = e.target.closest(selector);
                if (!target || target === dragged || target.parentElement !== container) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            container.addEventListener('drop', async (e) => {
                if (!dragged) return;
                const target = e.target.closest(selector);
                if (!target || target === dragged || target.parentElement !== container) return;
                e.preventDefault();

                const items = directItems(container, selector);
                const without = items.filter((item) => item !== dragged);
                const targetIndex = without.indexOf(target);
                const after = e.clientY > target.getBoundingClientRect().top + (target.offsetHeight / 2);
                const insertAt = targetIndex + (after ? 1 : 0);
                const ordered = [...without];
                ordered.splice(insertAt, 0, dragged);

                try {
                    const persisted = await applySavedOrder(container, selector, urlResolver(dragged, target), ordered);
                    if (!persisted) {
                        if (after) {
                            target.insertAdjacentElement('afterend', dragged);
                        } else {
                            target.insertAdjacentElement('beforebegin', dragged);
                        }
                        renumberStructureNames();
                    }
                } catch (err) {
                    alert(err.message || 'Não foi possível salvar a ordenação.');
                }
            });
        }

        // Remover módulo
        window.removeModulo = function(btn){
            const card = btn.closest('[data-modulo]');
            if (!card) return;
            card.remove();
            renumberStructureNames();
        };

        // Colapsar/expandir
        function setExpanded(card, expanded){
            const btn = card.querySelector('.toggle-modulo');
            const body = card.querySelector('.modulo-body');
            if (!btn || !body) return;
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            btn.querySelector('.i').textContent = expanded ? '▾' : '▸';
            body.style.display = expanded ? '' : 'none';
        }
        function bindModule(card){
            const btn = card.querySelector('.toggle-modulo');
            btn?.addEventListener('click', ()=>{
                const expanded = btn.getAttribute('aria-expanded') !== 'true';
                setExpanded(card, expanded);
            });
            setExpanded(card, true);
        }
        modWrap.querySelectorAll('[data-modulo]').forEach(bindModule);

        document.getElementById('btnExpandAll')?.addEventListener('click', ()=>{
            modWrap.querySelectorAll('[data-modulo]').forEach(card=> setExpanded(card, true));
        });
        document.getElementById('btnCollapseAll')?.addEventListener('click', ()=>{
            modWrap.querySelectorAll('[data-modulo]').forEach(card=> setExpanded(card, false));
        });

        // Templates
        function moduloTemplate(idx){
            return `
<div class="rounded-lg border p-0 overflow-hidden" data-modulo="${idx}">
  <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b">
    <div class="flex items-center gap-3">
      <button type="button" class="text-xs px-2 py-1 rounded border bg-white cursor-grab" data-drag-handle draggable="true">Arrastar</button>
      <button type="button" class="toggle-modulo h-8 w-8 rounded-md border bg-white hover:bg-slate-100 grid place-items-center" aria-expanded="true"><span class="i">▾</span></button>
      <div>
        <h3 class="font-semibold">Módulo <span class="mod-num">${idx+1}</span></h3>
        <div class="mt-1"><span class="pill bg-slate-100 text-slate-700 border border-slate-200">⏳ Sem prova</span></div>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" class="text-xs underline" data-action="move-modulo-up">Subir</button>
      <button type="button" class="text-xs underline" data-action="move-modulo-down">Descer</button>
      <button type="button" class="text-red-600 hover:underline" onclick="window.removeModulo(this)">Remover</button>
    </div>
  </div>
  <div class="modulo-body p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Título do Módulo</label>
        <input name="modulos[${idx}][titulo]" required data-required-field="1" data-label="Título do módulo" class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Descrição do Módulo</label>
        <textarea name="modulos[${idx}][descricao]" rows="3" class="js-ckeditor mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"></textarea>
      </div>
    </div>
    <div class="space-y-6" data-aulas></div>
    <div class="mt-4 flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <button type="button" class="btn btn-outline" data-action="add-aula">＋ Adicionar Aula</button>
        <span class="btn btn-soft opacity-60 cursor-not-allowed" title="Salve o curso para criar a prova">✏️ Criar Prova do Módulo</span>
      </div>
      <span class="text-xs text-slate-500">Organize as aulas e cadastre a prova do módulo quando estiver pronto</span>
    </div>
  </div>
</div>`;
        }

        function aulaTemplate(mIdx, aIdx){
            return `
<div class="aula-card grid grid-cols-1 md:grid-cols-4 gap-3 border rounded-md p-3 bg-white" data-aula="${aIdx}">
  <div class="md:col-span-4 flex justify-end">
    <button type="button" class="text-xs px-2 py-1 rounded border bg-white cursor-grab" data-drag-handle draggable="true">Arrastar aula</button>
  </div>
  <div class="md:col-span-2">
    <label class="block h-5 leading-5 text-sm font-medium whitespace-nowrap">Título da Aula</label>
    <input name="modulos[${mIdx}][aulas][${aIdx}][titulo]" required data-required-field="1" data-label="Título da aula" class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200" placeholder="Ex: Criando componentes">
  </div>
  <input type="hidden" name="modulos[${mIdx}][aulas][${aIdx}][duracao_minutos]" value="0">
  <div>
    <label class="block h-5 leading-5 text-sm font-medium text-center">Tipo</label>
    <select name="modulos[${mIdx}][aulas][${aIdx}][tipo]" required data-required-field="1" data-label="Tipo da aula" class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 bg-white focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
      <option value="">Selecione o tipo</option>
      <option value="video">Vídeo</option>
      <option value="texto">Texto</option>
      <option value="arquivo">Arquivo</option>
    </select>
  </div>
  <div class="md:col-span-4">
    <label class="text-sm font-medium">Descrição da Aula (opcional)</label>
    <textarea
      name="modulos[${mIdx}][aulas][${aIdx}][conteudo_texto]"
      class="js-ckeditor mt-1 w-full rounded-md border border-slate-300"
      rows="5"
      placeholder="Descreva os pontos principais desta aula..."
    ></textarea>
  </div>
  <div class="md:col-span-3">
    <label class="text-sm font-medium">URL do Conteúdo (opcional)</label>
    <input name="modulos[${mIdx}][aulas][${aIdx}][conteudo_url]" class="mt-1 w-full h-10 rounded-md border border-slate-300 px-3 focus:border-slate-400 focus:ring-2 focus:ring-slate-200" placeholder="https://...">
  </div>
  ${(Number(aIdx) > 0) ? `
  <div class="flex items-center gap-2">
    <input type="checkbox" name="modulos[${mIdx}][aulas][${aIdx}][liberada_apos_anterior]" value="1" class="h-4 w-4 border border-slate-300">
    <label class="text-sm">Liberar só após concluir aula anterior</label>
  </div>
  ` : ``}
  <div class="md:col-span-4 text-right">
    <button type="button" class="text-xs underline mr-2" data-action="move-aula-up">Subir</button>
    <button type="button" class="text-xs underline mr-2" data-action="move-aula-down">Descer</button>
    <button type="button" class="text-red-600 hover:underline" data-action="remove-aula">Remover aula</button>
  </div>
</div>`;
        }

        // util: recuperar índice do módulo pelo name
        function getModuloIndexFromNames(card){
            const any = card.querySelector('input[name^="modulos["], textarea[name^="modulos["], select[name^="modulos["]');
            const m = any?.name.match(/^modulos\[(\d+)\]/);
            return m ? parseInt(m[1],10) : null;
        }

        // Delegação: add-aula / remove-aula
        modWrap.addEventListener('click', (e)=>{
            const add = e.target.closest('[data-action="add-aula"]');
            if (add) {
                e.preventDefault();
                const card = add.closest('[data-modulo]');
                const mIdx = getModuloIndexFromNames(card);
                const cont = card.querySelector('[data-aulas]');
                if (!cont) return console.warn('Container de aulas não encontrado para módulo', mIdx);
                const next = cont.querySelectorAll('[data-aula]').length;
                cont.insertAdjacentHTML('beforeend', aulaTemplate(mIdx, next));
                window.initCKEditorsIn(cont); // inicializa CK nos novos textareas
                return;
            }
            const delQuiz = e.target.closest('[data-action="delete-quiz"]');
            if (delQuiz) {
                e.preventDefault();
                if (confirm('Excluir a prova deste módulo? As tentativas e respostas vinculadas também serão removidas.')) {
                    submitDynamicForm(delQuiz.dataset.url, {}, 'DELETE');
                }
                return;
            }
            const copyModule = e.target.closest('[data-action="copy-module"]');
            if (copyModule) {
                e.preventDefault();
                const wrap = copyModule.closest('.modulo-body');
                const destino = wrap?.querySelector('[data-copy-module-destino]')?.value;
                if (!destino) {
                    alert('Selecione o curso de destino.');
                    return;
                }
                submitDynamicForm(copyModule.dataset.url, { curso_destino_id: destino });
                return;
            }
            const rm = e.target.closest('[data-action="remove-aula"]');
            if (rm) {
                e.preventDefault();
                rm.closest('[data-aula]')?.remove();
                renumberStructureNames();
                return;
            }
            const moveModuleUp = e.target.closest('[data-action="move-modulo-up"]');
            if (moveModuleUp) {
                e.preventDefault();
                const item = moveModuleUp.closest('[data-modulo]');
                moveElementPersisted(item, -1, '[data-modulo]', modWrap, item?.dataset.reorderUrl || '');
                return;
            }
            const moveModuleDown = e.target.closest('[data-action="move-modulo-down"]');
            if (moveModuleDown) {
                e.preventDefault();
                const item = moveModuleDown.closest('[data-modulo]');
                moveElementPersisted(item, 1, '[data-modulo]', modWrap, item?.dataset.reorderUrl || '');
                return;
            }
            const moveAulaUp = e.target.closest('[data-action="move-aula-up"]');
            if (moveAulaUp) {
                e.preventDefault();
                const item = moveAulaUp.closest('[data-aula]');
                const container = item?.parentElement;
                moveElementPersisted(item, -1, '[data-aula]', container, container?.dataset.reorderUrl || '');
                return;
            }
            const moveAulaDown = e.target.closest('[data-action="move-aula-down"]');
            if (moveAulaDown) {
                e.preventDefault();
                const item = moveAulaDown.closest('[data-aula]');
                const container = item?.parentElement;
                moveElementPersisted(item, 1, '[data-aula]', container, container?.dataset.reorderUrl || '');
            }
        });

        function bindCourseDragDrop(root = document){
            if (modWrap && modWrap.dataset.dragBound !== '1') {
                modWrap.dataset.dragBound = '1';
                bindDragDrop(modWrap, '[data-modulo]', (dragged) => dragged?.dataset.reorderUrl || '');
            }

            root.querySelectorAll('[data-aulas]').forEach((container) => {
                if (container.dataset.dragBound === '1') return;
                container.dataset.dragBound = '1';
                bindDragDrop(container, '[data-aula]', () => container.dataset.reorderUrl || '');
            });
        }

        bindCourseDragDrop(document);

        // Adicionar módulo
        function addModulo(){
            const idx = modWrap.querySelectorAll('[data-modulo]').length;
            modWrap.insertAdjacentHTML('beforeend', moduloTemplate(idx));
            const card = modWrap.querySelector('[data-modulo]:last-child');
            bindModule(card);
            renumberModules();
            window.initCKEditorsIn(card); // inicializa CK no novo módulo
            bindCourseDragDrop(card);
        }
        addModuloBtn?.addEventListener('click', addModulo);

    })();
</script>



{{--@endsection--}}
