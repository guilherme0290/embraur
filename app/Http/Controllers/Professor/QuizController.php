<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\{Cursos, Modulos, Quiz, QuizQuestao, QuizOpcao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    /* =========================
     * LISTAGEM
     * ========================= */
    public function index(Request $r)
    {
        $cursoId  = $r->query('curso');
        $moduloId = $r->query('modulo');

        $quizzes = Quiz::with(['curso', 'modulo'])
            ->when($cursoId,  fn ($q) => $q->where('curso_id',  $cursoId))
            ->when($moduloId, fn ($q) => $q->where('modulo_id', $moduloId))
            ->withCount('questoes')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Filtro de cursos (pode filtrar pelos do professor, se houver vínculo)
        $cursos = Cursos::orderBy('titulo')->get();

        // Carregar módulos do curso selecionado
        $modulos = collect();
        if ($cursoId) {
            $modulos = Modulos::where('curso_id', $cursoId)
                ->orderBy('ordem')
                ->get();
        }

        return view('prof.quizzes.index', compact(
            'quizzes', 'cursos', 'modulos', 'cursoId', 'moduloId'
        ));
    }


    /* =========================
     * CREATE
     * ========================= */
    public function create(Request $r)
    {
        $curso  = Cursos::findOrFail($r->query('curso'));
        $modulo = Modulos::where('curso_id', $curso->id)->findOrFail($r->query('modulo'));

        // Cada módulo trabalha com uma única prova neste fluxo.
        $quiz = $modulo->quiz()->with('questoes.opcoes')->first();

        if ($quiz) {
            return redirect()->route('prof.quizzes.edit', $quiz);
        }

        $questoes = collect();

        return view('prof.quizzes.create', compact('curso','modulo','quiz','questoes'));
    }



    public function store(Request $r)
    {
        // Agora o escopo é SEMPRE módulo neste fluxo
        $data = $r->validate([
            'titulo'                  => ['nullable','string','max:255'],
            'escopo'                  => ['required', Rule::in(['modulo'])],
            'curso_id'                => ['required','exists:cursos,id'],
            'modulo_id'               => ['required','exists:modulos,id'],

            'questoes'                => ['required','array','min:1'],
            'questoes.*.enunciado'    => ['required','string'],
            'questoes.*.tipo'         => ['required', Rule::in(['multipla'])],
            'questoes.*.pontuacao'    => ['nullable','numeric','min:0.25'],

            'questoes.*.opcoes'             => ['nullable','array'],
            'questoes.*.opcoes.*.texto'     => ['required_with:questoes.*.opcoes','string','max:255'],
            'questoes.*.opcoes.*.correta'   => ['nullable','boolean'],
        ], $this->validationMessages(), $this->validationAttributes());

        // Coerência módulo⇄curso
        $mod = Modulos::findOrFail($data['modulo_id']);
        if ((int)$mod->curso_id !== (int)$data['curso_id']) {
            return back()->withErrors(['modulo_id' => 'O módulo selecionado não pertence ao curso informado.'])->withInput();
        }

        DB::transaction(function () use ($data) {
            $tituloAuto = $this->resolveTituloAutomatico($data);
            $quiz = Quiz::create([
                'titulo'          => $tituloAuto,
                'escopo'          => 'modulo',
                'curso_id'        => (int)$data['curso_id'],
                'modulo_id'       => (int)$data['modulo_id'],
                'correcao_manual' => false,
            ]);

            foreach ($data['questoes'] as $q) {
                $questao = $quiz->questoes()->create([
                    'enunciado' => $q['enunciado'],
                    'tipo'      => $q['tipo'],
                    'pontuacao' => $q['pontuacao'] ?? 1,
                ]);

                if (($q['tipo'] ?? 'multipla') === 'multipla') {
                    foreach (($q['opcoes'] ?? []) as $op) {
                        $questao->opcoes()->create([
                            'texto'   => $op['texto'],
                            'correta' => (bool)($op['correta'] ?? false),
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('prof.cursos.edit', $data['curso_id'])
            ->with('success', 'Prova criada com sucesso para o módulo!');
    }

    /* =========================
     * EDIT
     * ========================= */
    public function edit(Quiz $quiz)
    {
        $quiz->load(['curso', 'modulo', 'questoes.opcoes']);

        [$cursos, $modulosPorCurso] = $this->cursosEModulos();

        return view('prof.quizzes.edit', compact('quiz', 'cursos', 'modulosPorCurso'));
    }

    /* =========================
     * UPDATE (sincroniza questões/opções)
     * ========================= */
    public function update(Request $r, Quiz $quiz)
    {
        $data = $this->validatePayload($r, updating: true);

        $this->normalizeCursoModulo($data);

        DB::transaction(function () use ($quiz, $data) {
            $tituloAuto = $this->resolveTituloAutomatico($data);
            // Atualiza cabeçalho
            $quiz->fill([
                'titulo'    => $tituloAuto,
                'escopo'    => $data['escopo'],
                'curso_id'  => $data['curso_id'] ?? null,
                'modulo_id' => $data['modulo_id'] ?? null,
            ])->save();

            // Questões existentes x enviadas
            $existingQids = $quiz->questoes()->pluck('id')->all();
            $incomingQids = collect($data['questoes'])->pluck('id')->filter()->map(fn($v)=>(int)$v)->all();

            // Remove questões que sumiram
            $toDeleteQ = array_diff($existingQids, $incomingQids);
            if (!empty($toDeleteQ)) {
                QuizQuestao::whereIn('id', $toDeleteQ)->delete();
            }

            foreach ($data['questoes'] as $qIdx => $q) {
                // cria/atualiza a questão
                $questao = isset($q['id'])
                    ? QuizQuestao::where('quiz_id', $quiz->id)->findOrFail($q['id'])
                    : new QuizQuestao(['quiz_id' => $quiz->id]);

                $questao->fill([
                    'enunciado' => $q['enunciado'],
                    'tipo'      => $q['tipo'],
                    'pontuacao' => $q['pontuacao'] ?? 1,
                ])->save();

                // Sincroniza opções (somente se multipla)
                if (($q['tipo'] ?? 'multipla') === 'multipla') {
                    $existingOids = $questao->opcoes()->pluck('id')->all();
                    $incomingOids = collect($q['opcoes'] ?? [])->pluck('id')->filter()->map(fn($v)=>(int)$v)->all();

                    // Remove opções que sumiram
                    $toDeleteO = array_diff($existingOids, $incomingOids);
                    if (!empty($toDeleteO)) {
                        QuizOpcao::whereIn('id', $toDeleteO)->delete();
                    }

                    foreach (($q['opcoes'] ?? []) as $op) {
                        $opcao = isset($op['id'])
                            ? QuizOpcao::where('questao_id', $questao->id)->findOrFail($op['id'])
                            : new QuizOpcao(['questao_id' => $questao->id]);

                        $opcao->fill([
                            'texto'   => $op['texto'],
                            'correta' => (bool)($op['correta'] ?? false),
                        ])->save();
                    }
                } else {
                    // se virou "texto", elimina as opções de múltipla existentes
                    $questao->opcoes()->delete();
                }
            }

            // Com "multipla escolha" apenas, nunca há correção manual.
            $quiz->update(['correcao_manual' => false]);
        });

        return redirect()->route('prof.quizzes.edit', $quiz)->with('success', 'Quiz atualizado!');
    }

    /* =========================
     * DESTROY (opcional)
     * ========================= */
    public function destroy(Quiz $quiz)
    {
        $quiz->delete();
        return back()->with('success', 'Quiz removido.');
    }

    /* =========================
     * HELPERS
     * ========================= */

    /** Validação comum para store/update */
    private function validatePayload(Request $r, bool $updating = false): array
    {
        return $r->validate([
            'titulo'     => ['nullable','string','max:255'],
            'escopo'     => ['required', Rule::in(['curso','modulo'])],
            'curso_id'   => ['nullable','exists:cursos,id'],
            'modulo_id'  => ['nullable','exists:modulos,id'],

            // QUESTÕES
            'questoes'                   => ['required','array','min:1'],
            'questoes.*.id'             => $updating ? ['nullable','integer','exists:quiz_questoes,id'] : ['nullable'],
            'questoes.*.enunciado'      => ['required','string'],
            'questoes.*.tipo'           => ['required', Rule::in(['multipla'])],
            'questoes.*.pontuacao'      => ['nullable','numeric','min:0.25'],

            // OPÇÕES (para multipla)
            'questoes.*.opcoes'                 => ['nullable','array'],
            'questoes.*.opcoes.*.id'            => $updating ? ['nullable','integer','exists:quiz_opcoes,id'] : ['nullable'],
            'questoes.*.opcoes.*.texto'         => ['required_with:questoes.*.opcoes','string','max:255'],
            'questoes.*.opcoes.*.correta'       => ['nullable','boolean'],
        ], $this->validationMessages(), $this->validationAttributes());
    }

    private function validationMessages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'required_with' => 'O campo :attribute é obrigatório quando as opções da questão forem informadas.',
            'string' => 'O campo :attribute deve ser um texto.',
            'array' => 'O campo :attribute deve ser uma lista.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'exists' => 'O :attribute selecionado é inválido.',
            'in' => 'O :attribute selecionado é inválido.',
            'questoes.required' => 'Adicione pelo menos uma questão à prova.',
            'questoes.min' => 'Adicione pelo menos uma questão à prova.',
            'questoes.*.enunciado.required' => 'Informe o enunciado da questão.',
            'questoes.*.opcoes.*.texto.required_with' => 'Informe o texto da opção da questão.',
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'titulo' => 'título',
            'escopo' => 'tipo da prova',
            'curso_id' => 'curso',
            'modulo_id' => 'módulo',
            'questoes' => 'questões',
            'questoes.*.id' => 'questão',
            'questoes.*.enunciado' => 'enunciado da questão',
            'questoes.*.tipo' => 'tipo da questão',
            'questoes.*.pontuacao' => 'pontuação da questão',
            'questoes.*.opcoes' => 'opções da questão',
            'questoes.*.opcoes.*.id' => 'opção',
            'questoes.*.opcoes.*.texto' => 'texto da opção',
            'questoes.*.opcoes.*.correta' => 'opção correta',
        ];
    }

    /** Carrega cursos e um mapa [curso_id => módulos] para popular os selects */
    private function cursosEModulos(): array
    {
        // se houver vínculo com professor logado, filtre por ele
        $cursos = Cursos::orderBy('titulo')->get();
        $modulosPorCurso = Modulos::orderBy('ordem')->get()->groupBy('curso_id');

        return [$cursos, $modulosPorCurso];
    }

    /**
     * Garante coerência entre escopo/curso/módulo:
     * - escopo=curso  → modulo_id=null (curso_id obrigatório)
     * - escopo=modulo → modulo_id requerido, e curso_id = curso do módulo (se não vier)
     */
    private function normalizeCursoModulo(array &$data): void
    {
        if ($data['escopo'] === 'curso') {
            $data['modulo_id'] = null;
            if (empty($data['curso_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'curso_id' => 'Selecione o curso do quiz.',
                ]);
            }
        } else {
            // escopo = módulo
            if (empty($data['modulo_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'modulo_id' => 'Selecione o módulo do quiz.',
                ]);
            }
            $mod = Modulos::findOrFail($data['modulo_id']);
            // se não vier o curso, puxamos do módulo
            $data['curso_id'] = $data['curso_id'] ?? $mod->curso_id;

            // se vier, validamos coerência
            if ((int)$data['curso_id'] !== (int)$mod->curso_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'modulo_id' => 'O módulo selecionado não pertence ao curso informado.',
                ]);
            }
        }
    }

    private function resolveTituloAutomatico(array $data): string
    {
        $curso = !empty($data['curso_id']) ? Cursos::find($data['curso_id']) : null;
        $modulo = !empty($data['modulo_id']) ? Modulos::find($data['modulo_id']) : null;

        if (($data['escopo'] ?? null) === 'modulo' && $modulo) {
            $cursoTitulo = $curso?->titulo ?: 'Curso';
            return "Prova - {$cursoTitulo} - {$modulo->titulo}";
        }

        if ($curso) {
            return "Prova - {$curso->titulo}";
        }

        return 'Prova';
    }
}
