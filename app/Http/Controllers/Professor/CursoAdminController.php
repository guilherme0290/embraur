<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;

use App\Models\Aulas;
use App\Models\Categorias;
use App\Models\Cursos;
use App\Models\Modulos;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CursoAdminController extends Controller
{
    public function index(Request $request)
    {
        $profId = session('prof_id');
        $cursos = Cursos::with('categoria')
            ->where('professor_id', $profId)
            ->orderByDesc('id')
            ->paginate(12);



        return view('prof.cursos.index', compact('cursos'));
    }

    public function create()
    {
        $curso = new Cursos();
        $categorias = Categorias::orderBy('nome')->get();


        return view('prof.cursos.create', compact('curso', 'categorias'));
    }

    public function store(Request $request)
    {
        $profId = session('prof_id');

        $messages = [
            'required' => 'O campo :attribute é obrigatório.',
            'required_with' => 'O campo :attribute é obrigatório.',
            'numeric' => 'O campo :attribute deve ser numérico.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'max' => 'O campo :attribute deve ser no máximo :max.',
            'exists' => 'O valor informado para :attribute é inválido.',
            'in' => 'O valor informado para :attribute é inválido.',
        ];

        $attributes = [
            'categoria_id' => 'categoria',
            'titulo' => 'título do curso',
            'descricao_completa' => 'descrição completa',
            'nivel' => 'nível',
            'carga_horaria_horas' => 'carga horária do curso',
            'nota_minima_aprovacao' => 'nota mínima para aprovação',
            'preco' => 'preço',
            'preco_original' => 'preço original',
            'imagem_capa' => 'imagem do curso',
            'modulos' => 'módulos do curso',
            'modulos.*.aulas' => 'aulas do módulo',
            'modulos.*.titulo' => 'título do módulo',
            'modulos.*.aulas.*.titulo' => 'título da aula',
            'modulos.*.aulas.*.duracao_minutos' => 'duração da aula (min)',
            'modulos.*.aulas.*.tipo' => 'tipo da aula',
        ];

        $data = $request->validate([
            'categoria_id'          => ['required','exists:categorias,id'],
            'titulo'                => ['required','string','max:255'],
            //'descricao_curta'       => ['nullable','string'],
            'descricao_completa'    => ['required','string'],
            'nivel'                 => ['required', Rule::in(['todos','iniciante','intermediario','avancado'])],
            'carga_horaria_horas'   => ['required','numeric','min:0.1'], // campo da tela (horas)
            'maximo_alunos'         => ['nullable','integer','min:1'],
            'preco'                 => ['required','numeric','min:0'],
            'preco_original'        => ['required','numeric','min:0'],
            'nota_minima_aprovacao' => ['required','numeric','min:0','max:10'],
            'validade_dias'         => ['nullable','integer','min:1'],
            'status'                => ['nullable', Rule::in(['rascunho','publicado','arquivado'])],
            'imagem_capa'           => ['required','image','max:4096'],
            // estrutura
            'modulos'                              => ['nullable','array'],
            'modulos.*.titulo'                     => ['required','string','max:255'],
            'modulos.*.descricao'            => ['nullable','string'],
            'modulos.*.aulas'                      => ['nullable','array'],
            'modulos.*.aulas.*.titulo'             => ['required','string','max:255'],
            'modulos.*.aulas.*.duracao_minutos'    => ['nullable','integer','min:0'],
            'modulos.*.aulas.*.tipo'               => ['required', Rule::in(['video','texto','quiz','arquivo'])],
            'modulos.*.aulas.*.conteudo_url'       => ['nullable','string','max:255'],
            'modulos.*.aulas.*.conteudo_texto'     => ['nullable','string'],
            'modulos.*.aulas.*.descricao'     => ['nullable','string'],
            'modulos.*.aulas.*.liberada_apos_anterior' => ['nullable','boolean'],
            'modulos.*.aulas.*.video_file' => [
                'nullable',
                'file',
                'mimes:mp4,webm,ogg,mov,pdf,doc,docx',
                'max:1024000'
            ],
        ], $messages, $attributes);

        $dataCurso = collect($data)->only([
            'categoria_id','titulo','descricao_completa',
            'nivel','maximo_alunos','preco','preco_original','nota_minima_aprovacao',
            'validade_dias','status'
        ])->toArray();
        $dataCurso['nota_minima_aprovacao'] = $data['nota_minima_aprovacao'] ?? 0;

        $dataCurso['professor_id'] = $profId ?? null;
        // Horas (UI) → minutos (DB)
        $horas = (float)($data['carga_horaria_horas'] ?? 0);
        $dataCurso['carga_horaria_total'] = (int) round($horas * 60);

        // status pelo botão (opcional)
        $salvar = $request->input('salvar'); // 'rascunho' | 'publicar'
        if ($salvar === 'publicar') $dataCurso['status'] = 'publicado';
        if (empty($dataCurso['status'])) $dataCurso['status'] = 'rascunho';

        // capa
        if ($request->hasFile('imagem_capa')) {
            $dataCurso['imagem_capa'] = $request->file('imagem_capa')->store('cursos/capas', 'public');
        }

        DB::transaction(function () use (&$curso, $dataCurso, $data, $request) {
            $curso = Cursos::create($dataCurso);

            // módulos + aulas
            $ordemModulo = 1;
            foreach (($data['modulos'] ?? []) as $mIdx => $m) {
                if (empty($m['titulo'])) continue;

                $modulo = Modulos::create([
                    'curso_id'  => $curso->id,
                    'titulo'    => $m['titulo'],
                    'descricao' => $m['descricao'] ?? null,
                    'ordem'     => $ordemModulo++,
                ]);

                $ordemAula = 1;
                foreach (($m['aulas'] ?? []) as $aIdx => $a) {
                    if (empty($a['titulo'])) continue;

                    // Prioriza arquivo se enviado
                    $videoFile   = $request->file("modulos.$mIdx.aulas.$aIdx.video_file");
                    $conteudoUrl = $a['conteudo_url'] ?? null;
                    $tipo        = $a['tipo'] ?? 'video';

                    if ($videoFile) {
                        $dir  = "cursos/{$curso->id}/modulo-{$modulo->id}/videos";
                        $name = Str::slug($a['titulo'] ?? 'aula').'-'.Str::random(6).'.'.$videoFile->getClientOriginalExtension();
                        $path = $videoFile->storeAs($dir, $name, 'public');
                        $conteudoUrl = Storage::disk('public')->url($path);
                        $tipo = 'video';
                    }

                    Aulas::create([
                        'modulo_id'              => $modulo->id,
                        'titulo'                 => $a['titulo'],
                        'descricao'              => $a['descricao'] ?? null,
                        'tipo'                   => $tipo,
                        'duracao_minutos'        => (int)($a['duracao_minutos'] ?? 0),
                        'conteudo_url'           => $conteudoUrl,
                        'conteudo_texto'         => $a['conteudo_texto'] ?? null,
                        'ordem'                  => $ordemAula++,
                        'liberada_apos_anterior' => (bool)($a['liberada_apos_anterior'] ?? false),
                    ]);
                }
            }

            // se a carga horária não foi preenchida, calculamos pela soma das aulas
            if (empty($dataCurso['carga_horaria_total'])) {
                $min = $curso->modulos()->withSum('aulas', 'duracao_minutos')->get()
                    ->sum('aulas_sum_duracao_minutos');
                $curso->update(['carga_horaria_total' => (int)$min]);
            }
        });



        return redirect()->route('prof.cursos.edit', $curso)->with('success','Curso criado com sucesso!');

    }

    public function edit(Cursos $curso)
    {
        $this->authorizeCurso($curso);
        $categorias = Categorias::orderBy('ordem_exibicao')->orderBy('nome')->get();
        $curso->load([
            'categoria',
            'modulos' => fn($q) => $q->orderBy('ordem'),
            'modulos.aulas' => fn($q) => $q->orderBy('ordem'),
            'modulos.quiz',
        ]);

        $quizzesDoCurso = Quiz::where('curso_id', $curso->id)->get(['id','titulo']);
        $cursosDoProfessor = Cursos::where('professor_id', session('prof_id'))
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
        $modulosImportaveis = Modulos::with('curso:id,titulo')
            ->withCount('aulas')
            ->withExists('quiz')
            ->whereHas('curso', fn($q) => $q->where('professor_id', session('prof_id')))
            ->where('curso_id', '!=', $curso->id)
            ->orderBy('curso_id')
            ->orderBy('ordem')
            ->get(['id', 'curso_id', 'titulo']);

        return view('prof.cursos.edit', compact('curso','categorias','quizzesDoCurso','cursosDoProfessor','modulosImportaveis'));


    }

    public function update(Request $request, Cursos $curso)
    {
        $this->authorizeCurso($curso);

        $messages = [
            'required' => 'O campo :attribute é obrigatório.',
            'required_with' => 'O campo :attribute é obrigatório.',
            'numeric' => 'O campo :attribute deve ser numérico.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'max' => 'O campo :attribute deve ser no máximo :max.',
            'exists' => 'O valor informado para :attribute é inválido.',
            'in' => 'O valor informado para :attribute é inválido.',
        ];

        $attributes = [
            'categoria_id' => 'categoria',
            'titulo' => 'título do curso',
            'descricao_completa' => 'descrição completa',
            'nivel' => 'nível',
            'carga_horaria_horas' => 'carga horária do curso',
            'nota_minima_aprovacao' => 'nota mínima para aprovação',
            'preco' => 'preço',
            'preco_original' => 'preço original',
            'imagem_capa' => 'imagem do curso',
            'modulos' => 'módulos do curso',
            'modulos.*.aulas' => 'aulas do módulo',
            'modulos.*.titulo' => 'título do módulo',
            'modulos.*.aulas.*.titulo' => 'título da aula',
            'modulos.*.aulas.*.duracao_minutos' => 'duração da aula (min)',
            'modulos.*.aulas.*.tipo' => 'tipo da aula',
        ];

        $data = $request->validate([
            'categoria_id'          => ['required','exists:categorias,id'],
            'titulo'                => ['required','string','max:255'],
            //'descricao_curta'       => ['nullable','string'],
            'descricao_completa'    => ['required','string'],
            'nivel'                 => ['required', Rule::in(['todos','iniciante','intermediario','avancado'])],
            'carga_horaria_horas'   => ['required','numeric','min:0.1'],
            'preco'                 => ['required','numeric','min:0'],
            'preco_original'        => ['required','numeric','min:0'],
            'nota_minima_aprovacao' => ['required','numeric','min:0','max:10'],
            'validade_dias'         => ['nullable','integer','min:1'],
            'status'                => ['nullable', Rule::in(['rascunho','publicado','arquivado'])],
            'imagem_capa'           => ['nullable','image','max:4096'],

            // estrutura (update)
            'modulos'                              => ['required','array','min:1'],
            'modulos.*.id'                         => ['nullable','exists:modulos,id'],
            'modulos.*.titulo'                     => ['required','string','max:255'],
            'modulos.*.descricao'                  => ['nullable','string'],
            'modulos.*.aulas'                      => ['required','array','min:1'],
            'modulos.*.aulas.*.id'                 => ['nullable','exists:aulas,id'],
            'modulos.*.aulas.*.titulo'             => ['required','string','max:255'],
            'modulos.*.aulas.*.duracao_minutos'    => ['nullable','integer','min:0'],
            'modulos.*.aulas.*.tipo'               => ['required', Rule::in(['video','texto','quiz','arquivo'])],
            'modulos.*.aulas.*.conteudo_url'       => ['nullable','string','max:255'],
            'modulos.*.aulas.*.conteudo_texto'       => ['nullable','string'],
            'modulos.*.aulas.*.descricao'     => ['nullable','string'],
            'modulos.*.aulas.*.liberada_apos_anterior' => ['nullable','boolean'],
            'modulos.*.aulas.*.quiz_id'            => ['nullable','exists:quizzes,id'],
            'modulos.*.aulas.*.video_file' => [
                'nullable',
                'file',
                'mimes:mp4,webm,ogg,mov,pdf,doc,docx',
                'max:1024000'
            ],
        ], $messages, $attributes);

        // payload curso
        $payload = collect($data)->except(['imagem_capa','modulos'])->toArray();
        $payload['nota_minima_aprovacao'] = $data['nota_minima_aprovacao'] ?? 0;

        // horas (se vierem como input futuro)
        if (isset($payload['carga_horaria_horas'])) {
            $payload['carga_horaria_total'] = (int) round(((float)$payload['carga_horaria_horas']) * 60);
            unset($payload['carga_horaria_horas']);
        }

        $salvar = $request->input('salvar'); // 'rascunho' | 'publicar'
        $payload['status'] =  $salvar === 'publicar' ? 'publicado' : 'rascunho';

        // troca capa (se enviada)
        if ($request->hasFile('imagem_capa')) {
            if ($curso->imagem_capa && Storage::disk('public')->exists($curso->imagem_capa)) {
                Storage::disk('public')->delete($curso->imagem_capa);
            }
            $payload['imagem_capa'] = $request->file('imagem_capa')->store('cursos/capas', 'public');
        }

        DB::transaction(function () use ($curso, $payload, $data, $request) {

            // 1) Atualiza o curso
            $curso->update($payload);

            // 2) Sincroniza módulos e aulas (se veio 'modulos')
            if (array_key_exists('modulos', $data)) {

                // carrega estrutura atual para diffs
                $curso->load(['modulos.aulas']);
                $modulosAtuais = $curso->modulos;
                $idsModulosAtuais = $modulosAtuais->pluck('id')->all();

                $idsModulosRecebidos = [];
                $ordemModulo = 1;

                foreach (($data['modulos'] ?? []) as $mIdx => $m) {
                    $modId = $m['id'] ?? null;

                    if ($modId) {
                        $modulo = $modulosAtuais->firstWhere('id', $modId);
                        if (!$modulo) continue;
                        $modulo->update([
                            'titulo' => $m['titulo'],
                            'descricao' => $m['descricao'] ?? null,
                            'ordem' => $ordemModulo++,
                        ]);
                    } else {
                        $modulo = Modulos::create([
                            'curso_id' => $curso->id,
                            'titulo' => $m['titulo'],
                            'descricao' => $m['descricao'] ?? null,
                            'ordem' => $ordemModulo++,
                        ]);
                    }

                    $idsModulosRecebidos[] = $modulo->id;

                    // === AULAS ===
                    $aulasAtuais = $modulo->aulas;
                    $idsAulasAtuais = $aulasAtuais->pluck('id')->all();
                    $idsAulasRecebidas = [];
                    $ordemAula = 1;

                    foreach (($m['aulas'] ?? []) as $aIdx => $a) {
                        $aulaId = $a['id'] ?? null;

                        $payloadAula = [
                            'titulo' => $a['titulo'],
                            'descricao' => $a['descricao'] ?? null,
                            'tipo' => $a['tipo'] ?? 'video',
                            'duracao_minutos' => (int)($a['duracao_minutos'] ?? 0),
                            'conteudo_url' => $a['conteudo_url'] ?? null,
                            'conteudo_texto' => $a['conteudo_texto'] ?? null,
                            'ordem' => $ordemAula++,
                            'liberada_apos_anterior' => (bool)($a['liberada_apos_anterior'] ?? false),
                            'quiz_id' => $a['quiz_id'] ?? null,
                        ];

                        // Se arquivo foi enviado, prioriza-o
                        $videoFile = $request->file("modulos.$mIdx.aulas.$aIdx.video_file");
                        if ($videoFile) {
                            // apaga local antigo se existia
                            if ($aulaId) {
                                $aulaExistente = $aulasAtuais->firstWhere('id', $aulaId);
                                if ($aulaExistente && $aulaExistente->conteudo_url && Str::startsWith($aulaExistente->conteudo_url, '/storage/')) {
                                    $rel = Str::after($aulaExistente->conteudo_url, '/storage/');
                                    Storage::disk('public')->delete($rel);
                                }
                            }
                            $dir = "cursos/{$curso->id}/modulo-{$modulo->id}/videos";
                            $name = Str::slug($a['titulo'] ?? 'aula') . '-' . Str::random(6) . '.' . $videoFile->getClientOriginalExtension();
                            $path = $videoFile->storeAs($dir, $name, 'public');
                            $payloadAula['conteudo_url'] = Storage::disk('public')->url($path);
                            $payloadAula['tipo'] = 'video';
                        }

                        if ($aulaId) {
                            $aula = $aulasAtuais->firstWhere('id', $aulaId);
                            if (!$aula) continue;
                            $aula->update($payloadAula);
                        } else {
                            $aula = Aulas::create($payloadAula + ['modulo_id' => $modulo->id]);
                        }

                        $idsAulasRecebidas[] = $aula->id;
                    }

                    // DELETE aulas ausentes
                    $idsAulasParaExcluir = array_diff($idsAulasAtuais, $idsAulasRecebidas);
                    if (!empty($idsAulasParaExcluir)) {
                        // apaga os vídeos locais dessas aulas (se houver)
                        foreach ($aulasAtuais->whereIn('id', $idsAulasParaExcluir) as $ax) {
                            if ($ax->conteudo_url && Str::startsWith($ax->conteudo_url, '/storage/')) {
                                $rel = Str::after($ax->conteudo_url, '/storage/');
                                Storage::disk('public')->delete($rel);
                            }
                        }
                        Aulas::whereIn('id', $idsAulasParaExcluir)->delete();
                    }
                }

                // DELETE módulos ausentes
                $idsModulosParaExcluir = array_diff($idsModulosAtuais, $idsModulosRecebidos);
                if (!empty($idsModulosParaExcluir)) {
                    // (se quiser, percorra e apague vídeos locais das aulas desses módulos)
                    Modulos::whereIn('id', $idsModulosParaExcluir)->delete();
                }

                // recalcular carga horária se necessário
                if (empty($payload['carga_horaria_total'])) {
                    $min = $curso->modulos()->withSum('aulas', 'duracao_minutos')->get()
                        ->sum('aulas_sum_duracao_minutos');
                    $curso->update(['carga_horaria_total' => (int)$min]);
                }
            }
        });

        return back()->with('success','Curso atualizado com sucesso!');
    }


    public function destroy(Cursos $curso)
    {

        $this->authorizeCurso($curso);

        if ($curso->imagem_capa && Storage::disk('public')->exists($curso->imagem_capa)) {
            Storage::disk('public')->delete($curso->imagem_capa);
        }
        $curso->delete();
        return redirect()->route('prof.cursos.index')->with('success', 'Curso removido.');
    }

    private function authorizeCurso(Cursos $curso)
    {
        if ($curso->professor_id != session('prof_id')) {
            abort(403, 'Sem permissão para esse curso.');
        }
    }

    private function saveVideoAndGetUrl(UploadedFile $file, Cursos $curso, int $moduloId, string $titulo = 'aula'): string
    {
        $dir   = "cursos/{$curso->id}/modulo-{$moduloId}/videos";
        $name  = Str::slug($titulo).'-'.Str::random(6).'.'.$file->getClientOriginalExtension();
        $path  = $file->storeAs($dir, $name, 'public');      // storage/app/public/...
        return Storage::disk('public')->url($path);          // /storage/...
    }

    private function deleteIfLocalUrl(?string $url): void
    {
        if (!$url) return;
        $prefix = '/storage/';
        if (Str::startsWith($url, $prefix)) {
            $rel = Str::after($url, $prefix);                // caminho relativo dentro do disk public
            Storage::disk('public')->delete($rel);
        }
    }
}
