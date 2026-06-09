<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Cursos;         // <-- ajuste para App\Models\Curso se seu model for singular
use App\Models\ProgressoAula;
use Illuminate\Support\Facades\DB;

// <-- garante o import do model de progresso

class Matriculas extends Model
{
    protected $table = 'matriculas';
    public $timestamps = false;
    protected $appends = ['status_exibicao'];

    protected $fillable = [
        'aluno_id','curso_id','data_matricula','data_inicio','data_conclusao','data_vencimento',
        'progresso_porcentagem','status','nota_final','ciclo_numero','recertificacao_de_matricula_id'
    ];

    protected $casts = [
        'data_matricula' => 'datetime',
        'data_inicio'    => 'datetime',
        'data_conclusao' => 'datetime',
        'data_vencimento'=> 'datetime',
    ];

    // aluno_id -> users.id
    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    // curso_id -> cursos.id  (ou 'curso_id' -> 'curso.id' se seu model/tabela for singular)
    public function curso()
    {
        return $this->belongsTo(Cursos::class, 'curso_id'); // troque para Curso::class se o model for singular
    }

    public function recertificacaoDe()
    {
        return $this->belongsTo(self::class, 'recertificacao_de_matricula_id');
    }

    public function recertificacoes()
    {
        return $this->hasMany(self::class, 'recertificacao_de_matricula_id');
    }

    public function certificado()
    {
        return $this->hasMany(Certificados::class, 'matricula_id'); // troque para Curso::class se o model for singular
    }

    // progresso_aulas.matricula_id -> matriculas.id
    public function progressoAulas()
    {
        return $this->hasMany(ProgressoAula::class, 'matricula_id');
    }


    public function scopeDoProfessor($q, int $profId)
    {
        return $q->join('cursos','cursos.id','=','matriculas.curso_id')
            ->where('cursos.professor_id',$profId);
    }

    public function scopeDoAlunoCurso($q, int $alunoId, int $cursoId)
    {
        return $q->where('aluno_id', $alunoId)->where('curso_id', $cursoId);
    }

    public function estaExpiradaPorData(): bool
    {
        return $this->data_vencimento instanceof Carbon && $this->data_vencimento->isPast();
    }

    public function getStatusExibicaoAttribute(): string
    {
        return $this->estaExpiradaPorData() ? 'expirado' : (string) ($this->status ?? 'ativo');
    }

    public function podeGerarNovoCiclo(): bool
    {
        return $this->status_exibicao === 'expirado';
    }

    public static function atualDoAlunoCurso(int $alunoId, int $cursoId, ?int $matriculaId = null): self
    {
        $base = self::doAlunoCurso($alunoId, $cursoId);

        if ($matriculaId) {
            return (clone $base)->where('id', $matriculaId)->firstOrFail();
        }

        return $base
            ->orderByRaw("CASE WHEN data_vencimento IS NULL OR data_vencimento >= CURRENT_TIMESTAMP THEN 0 ELSE 1 END")
            ->orderByDesc('data_matricula')
            ->orderByDesc('id')
            ->firstOrFail();
    }

    public static function possuiCicloVigente(int $alunoId, int $cursoId): bool
    {
        return self::doAlunoCurso($alunoId, $cursoId)
            ->where(function ($q) {
                $q->whereNull('data_vencimento')
                    ->orWhere('data_vencimento', '>=', now());
            })
            ->whereIn('status', ['ativo', 'concluido'])
            ->exists();
    }

    public static function criarNovoCiclo(int $alunoId, Cursos $curso): self
    {
        $ultima = self::doAlunoCurso($alunoId, (int) $curso->id)
            ->orderByDesc('ciclo_numero')
            ->orderByDesc('id')
            ->first();

        $agora = now();
        $validadeDias = (int) ($curso->validade_dias ?? 0);

        return self::create([
            'aluno_id' => $alunoId,
            'curso_id' => $curso->id,
            'data_matricula' => $agora,
            'data_inicio' => $agora,
            'data_conclusao' => null,
            'data_vencimento' => $validadeDias > 0 ? $agora->copy()->addDays($validadeDias) : null,
            'progresso_porcentagem' => 0,
            'nota_final' => null,
            'status' => 'ativo',
            'ciclo_numero' => ((int) ($ultima->ciclo_numero ?? 0)) + 1,
            'recertificacao_de_matricula_id' => $ultima?->id,
        ]);
    }

    /** Percentual concluído baseado em QUIZZES respondidos do aluno neste curso */
    public function percentQuizzes(): int
    {
        $cursoId = (int) $this->curso_id;

        // total de quizzes do curso
        $total = (int) DB::table('quizzes')
            ->where('curso_id', $cursoId)
            ->count('id');

        if ($total === 0) return 0;

        // quizzes executados pelo aluno (houve conclusão)
        $ok = (int) DB::table('quiz_tentativas as qt')
            ->join('quizzes as q', 'q.id', '=', 'qt.quiz_id')
            ->where('q.curso_id', $cursoId)
            ->where('qt.matricula_id', $this->id)
            ->whereNotNull('qt.concluido_em')
            ->distinct('qt.quiz_id')
            ->count('qt.quiz_id');

        return (int) round(min(100, ($ok / $total) * 100));
    }

    public function lastQuizAt(): ?Carbon
    {
        $cursoId = (int) $this->curso_id;

        $ts = DB::table('quiz_tentativas as qt')
            ->join('quizzes as q', 'q.id', '=', 'qt.quiz_id')
            ->where('q.curso_id', $cursoId)
            ->where('qt.matricula_id', $this->id)
            ->whereNotNull('qt.concluido_em')
            ->max('qt.concluido_em');

        if ($ts) return Carbon::parse($ts);

        return $this->data_matricula instanceof Carbon ? $this->data_matricula : null;
    }
}
