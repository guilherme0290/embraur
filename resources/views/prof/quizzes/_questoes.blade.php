<div class="rounded-xl border p-4">
    <div class="mb-3">
        <h2 class="font-semibold">Questões</h2>
    </div>

    <div id="questoesWrap" class="space-y-4">
        @forelse($questoes as $qIdx => $questao)
            <div class="questao-card border rounded-md p-4 bg-slate-50" data-q="{{ $qIdx }}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold">Questão <span class="q-num">{{ $qIdx+1 }}</span></h4>
                    <button type="button" class="text-red-600 text-xs"
                            onclick="this.closest('.questao-card').remove(); window.__quizzes_renumberQuestoes()">Remover</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium">Enunciado *</label>
                        <textarea
                            id="enunciado-{{ $qIdx }}"
                            data-role="enunciado"
                            name="questoes[{{ $qIdx }}][enunciado]"
                            rows="6"
                            class="mt-1 w-full rounded-md border px-3 py-2"
                        >{!! old("questoes.$qIdx.enunciado", $questao['enunciado'] ?? '') !!}</textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Tipo *</label>
                        <input type="text" value="Múltipla Escolha" readonly
                               class="mt-1 w-full h-10 rounded-md border px-3 bg-slate-100 text-slate-700 cursor-not-allowed">
                        <input type="hidden" name="questoes[{{ $qIdx }}][tipo]" value="multipla" data-role="tipo">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Pontuação</label>
                        <input type="number" min="0.25" step="0.25"
                               name="questoes[{{ $qIdx }}][pontuacao]"
                               value="{{ $questao['pontuacao'] ?? 1 }}"
                               class="mt-1 w-full h-10 rounded-md border px-3">
                    </div>
                </div>

                {{-- Opções (mostra somente em múltipla) --}}
                @php $opcoes = $questao['opcoes'] ?? [['texto'=>'']]; @endphp
                <div class="mt-3 space-y-2 opcoesWrap">
                    @foreach($opcoes as $oIdx => $op)
                        <div class="flex items-center gap-2 border rounded px-3 py-2 bg-white" data-op>
                            <input type="text" name="questoes[{{ $qIdx }}][opcoes][{{ $oIdx }}][texto]"
                                   value="{{ $op['texto'] ?? '' }}" placeholder="Opção..."
                                   class="flex-1 h-9 rounded-md border px-2">
                            <label class="flex items-center gap-1 text-sm">
                                <input type="checkbox" name="questoes[{{ $qIdx }}][opcoes][{{ $oIdx }}][correta]" value="1"
                                    @checked(!empty($op['correta']))> Correta
                            </label>
                            <button type="button" class="text-red-600 text-xs"
                                    onclick="this.closest('[data-op]').remove()">Remover</button>
                        </div>
                    @endforeach

                    <button type="button" class="text-xs px-2 py-1 rounded border hover:bg-slate-50"
                            onclick="window.__quizzes_addOpcao && window.__quizzes_addOpcao(this)">＋ Adicionar Opção</button>

                </div>
            </div>

        @empty
            {{-- vazio; o JS cria a primeira questão --}}
        @endforelse

    </div>

    <div class="mt-4 flex justify-end">
        <button type="button" class="btn btn-outline h-9" id="btnAddQuestao">＋ Adicionar questão</button>
    </div>
</div>

<!-- Import do CKEditor (uma vez na página) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<script>
    (function(){
        // Evita bind duplicado se o partial for incluído mais de uma vez
        if (window.__quizzes_bound) return;
        window.__quizzes_bound = true;

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

        const wrap   = document.getElementById('questoesWrap');
        const addBtn = document.getElementById('btnAddQuestao');

        // --- CKEditor registry ---
        const editors = new Map(); // textareaEl -> editorInstance

        function initCkOn(textarea) {
            if (!textarea || editors.has(textarea)) return;

            ClassicEditor.create(textarea, {
                language: 'pt-br',
                toolbar: { items: toolbar },
                ckfinder: { uploadUrl: UPLOAD_URL },
                mediaEmbed,
                htmlSupport,
                removePlugins: ['CKBox','CKFinder','EasyImage']
            })
                .then(editor => editors.set(textarea, editor))
                .catch(console.error);
        }

        function destroyEditor(textarea) {
            const ed = editors.get(textarea);
            if (ed) {
                ed.destroy().catch(console.error);
                editors.delete(textarea);
            }
        }

        // --- Renumeração das questões e atualização dos names ---
        function renumberQuestoes(){
            if (!wrap) return;

            wrap.querySelectorAll('.questao-card').forEach((card,i)=>{
                card.dataset.q = i;
                const num = card.querySelector('.q-num');
                if (num) num.textContent = i + 1;

                // Atualiza apenas o primeiro índice [n] (questões)
                card.querySelectorAll('[name]').forEach(inp=>{
                    inp.name = inp.name.replace(/questoes\[\d+\]/, `questoes[${i}]`);
                });

                // Mostra/esconde wrapper de opções conforme tipo
                const sel = card.querySelector('[data-role="tipo"]');
                const opw = card.querySelector('.opcoesWrap');
                if (sel && opw) opw.style.display = (sel.value === 'multipla') ? '' : 'none';
            });
        }
        window.__quizzes_renumberQuestoes = renumberQuestoes; // caso seja chamado externamente

        // --- Adicionar/Remover Opção ---
        function addOpcao(btn){
            const card   = btn.closest('.questao-card');
            const qIdx   = +card.dataset.q;
            const opWrap = card.querySelector('.opcoesWrap');
            const next   = opWrap.querySelectorAll('[data-op]').length;

            const tpl = `
      <div class="flex items-center gap-2 border rounded px-3 py-2 bg-white" data-op>
        <input type="text" name="questoes[${qIdx}][opcoes][${next}][texto]" placeholder="Opção..."
               class="flex-1 h-9 rounded-md border px-2">
        <label class="flex items-center gap-1 text-sm">
          <input type="checkbox" name="questoes[${qIdx}][opcoes][${next}][correta]" value="1"> Correta
        </label>
        <button type="button" class="text-red-600 text-xs" data-action="remover-opcao">Remover</button>
      </div>`;
            btn.insertAdjacentHTML('beforebegin', tpl);
        }
        window.__quizzes_addOpcao = addOpcao; // se quiser chamar direto

        // --- Remover Questão (com cleanup dos editores) ---
        function removeQuestao(card){
            // Destrói qualquer editor dentro do card antes de remover
            card.querySelectorAll('textarea[data-role="enunciado"]').forEach(destroyEditor);
            card.remove();
            renumberQuestoes();
        }

        // --- Template do Card de Questão ---
        function questaoTemplate(idx){
            return `
      <div class="questao-card border rounded-md p-4 bg-slate-50" data-q="${idx}">
        <div class="flex justify-between items-center mb-3">
          <h4 class="font-semibold">Questão <span class="q-num">${idx+1}</span></h4>
          <button type="button" class="text-red-600 text-xs" data-action="remover-questao">Remover</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="md:col-span-2">
            <label class="text-sm font-medium">Enunciado *</label>
            <textarea name="questoes[${idx}][enunciado]" rows="3"
                      data-role="enunciado"
                      class="mt-1 w-full rounded-md border px-3 py-2"></textarea>
          </div>
          <div>
            <label class="text-sm font-medium">Tipo *</label>
            <input type="text" value="Múltipla Escolha" readonly
                   class="mt-1 w-full h-10 rounded-md border px-3 bg-slate-100 text-slate-700 cursor-not-allowed">
            <input type="hidden" name="questoes[${idx}][tipo]" value="multipla" data-role="tipo">
          </div>
          <div>
            <label class="text-sm font-medium">Pontuação</label>
            <input type="number" min="0.25" step="0.25" name="questoes[${idx}][pontuacao]" value="1"
                   class="mt-1 w-full h-10 rounded-md border px-3">
          </div>
        </div>

        <div class="mt-3 space-y-2 opcoesWrap">
          <div class="flex items-center gap-2 border rounded px-3 py-2 bg-white" data-op>
            <input type="text" name="questoes[${idx}][opcoes][0][texto]" placeholder="Opção..."
                   class="flex-1 h-9 rounded-md border px-2">
            <label class="flex items-center gap-1 text-sm">
              <input type="checkbox" name="questoes[${idx}][opcoes][0][correta]" value="1"> Correta
            </label>
            <button type="button" class="text-red-600 text-xs" data-action="remover-opcao">Remover</button>
          </div>
          <button type="button" class="text-xs px-2 py-1 rounded border hover:bg-slate-50"
                  data-action="adicionar-opcao">＋ Adicionar Opção</button>
        </div>
      </div>`;
        }

        // --- Adicionar Questão ---
        function addQuestao(){
            if (!wrap) return;
            const idx = wrap.querySelectorAll('.questao-card').length;
            wrap.insertAdjacentHTML('beforeend', questaoTemplate(idx));
            const card = wrap.querySelector(`.questao-card[data-q="${idx}"]`);

            // Inicializa CKEditor apenas para o novo textarea
            const newTextarea = card.querySelector('textarea[data-role="enunciado"]');
            initCkOn(newTextarea);

            // Ajusta visibilidade das opções conforme tipo atual
            const sel = card.querySelector('[data-role="tipo"]');
            const opw = card.querySelector('.opcoesWrap');
            if (sel && opw) opw.style.display = (sel.value === 'multipla') ? '' : 'none';

            renumberQuestoes();
        }
        window.__quizzes_addQuestao = addQuestao; // se quiser chamar fora

        // --- Delegação de eventos (click/change) ---
        function bind(){
            // Botão "Adicionar Questão"
            addBtn && addBtn.addEventListener('click', addQuestao);

            // Delegação no wrap para lidar com ações dos cards
            wrap?.addEventListener('click', (e)=>{
                const t = e.target;

                if (t.matches('[data-action="remover-questao"]')){
                    const card = t.closest('.questao-card');
                    if (card) removeQuestao(card);
                }

                if (t.matches('[data-action="adicionar-opcao"]')){
                    addOpcao(t);
                }

                if (t.matches('[data-action="remover-opcao"]')){
                    t.closest('[data-op]')?.remove();
                    // Não há CKEditor nas opções; nada a destruir aqui
                }
            });

            // Mostrar/esconder opções quando muda o tipo
            wrap?.addEventListener('change', (e)=>{
                if(e.target.matches('[data-role="tipo"]')){
                    const card = e.target.closest('.questao-card');
                    const opw  = card.querySelector('.opcoesWrap');
                    if(opw) opw.style.display = e.target.value === 'multipla' ? '' : 'none';
                }
            });

            // Inicializa CKEditor nos textareas já existentes
            document.querySelectorAll('textarea[data-role="enunciado"]').forEach(initCkOn);

            // Sincroniza dados dos editores no submit do primeiro <form> ancestral
            const form = wrap?.closest('form') || document.querySelector('form');
            if (form) {
                form.addEventListener('submit', () => {
                    for (const [textarea, ed] of editors.entries()) {
                        textarea.value = ed.getData();
                    }
                });
            }
        }

        // (Opcional) Segurança extra: observer para capturar remoções diretas fora do fluxo normal
        const mo = new MutationObserver(muts => {
            for (const m of muts) {
                m.removedNodes.forEach(n => {
                    if (n.nodeType === 1) {
                        n.querySelectorAll?.('textarea[data-role="enunciado"]').forEach(destroyEditor);
                        if (n.matches?.('textarea[data-role="enunciado"]')) destroyEditor(n);
                    }
                });
            }
        });
        mo.observe(document.body, { childList: true, subtree: true });

        // Start!
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bind);
        } else {
            bind();
        }

    })();
</script>
