<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Cursos extends Model
{

    protected $table = 'cursos';
    public $timestamps = false; // suas migrations não usam created_at/updated_at

    protected $appends = ['imagem_capa_url', 'carga_horaria'];

    protected $fillable = [
        'professor_id',
        'categoria_id',
        'titulo',
        'descricao_curta',
        'descricao_completa',
        'conteudo_programatico',
        'imagem_capa',
        'video_introducao',
        'nivel',                  // ['iniciante','intermediario','avancado']
        'carga_horaria_total',
        'preco',
        'preco_original',
        'nota_minima_aprovacao',  // usado para travar/destravar módulos (>= 7.0)
        'status',      // ex.: 'rascunho','publicado','oculto'

    ];

    protected $casts = [
        'preco'                  => 'decimal:2',
        'preco_original'         => 'decimal:2',
        'carga_horaria_total'    => 'integer',
        'nota_minima_aprovacao'  => 'float',
    ];

    /* ----------------------------- RELACIONAMENTOS ----------------------------- */

    public function instrutor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'categoria_id');
    }

    public function modulos()
    {
        return $this->hasMany(Modulos::class, 'curso_id')->orderBy('ordem');
    }

    public function matriculas()
    {
        return $this->hasMany(Matriculas::class, 'curso_id');
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    /**
     * Todas as aulas do curso via módulos (usado para withCount('aulas as aulas_total')).
     */
    public function aulas()
    {
        return $this->hasManyThrough(
            Aulas::class,    // model final
            Modulos::class,  // model intermediário
            'curso_id',      // FK em Modulos -> cursos.id
            'modulo_id',     // FK em Aulas -> modulos.id
            'id',            // PK em Cursos
            'id'             // PK em Modulos
        );
    }

    /**
     * Quizzes do curso (cada módulo pode ter um quiz).
     */
    public function quizzes()
    {
        return $this->hasManyThrough(
            Quiz::class,     // model final
            Modulos::class,  // model intermediário
            'curso_id',      // FK em Modulos
            'modulo_id',     // FK em Quiz
            'id',            // PK em Cursos
            'id'             // PK em Modulos
        );
    }

    /* --------------------------------- SCOPES --------------------------------- */

    public function scopePublicados($query)
    {
        return $query->where('status_publicacao', 'publicado');
    }

    /* ------------------------------- ACCESSORS -------------------------------- */

    public function getImagemCapaUrlAttribute()
    {
        if (!$this->imagem_capa) {
            return  Storage::url('cursos/capas/image_placeholder.jpg');
        }
        return $this->imagem_capa ? Storage::url($this->imagem_capa) : null;
    }

    public function getCargaHorariaAttribute(): ?string
    {
        $minutos = (int) ($this->carga_horaria_total ?? 0);

        if ($minutos <= 0) {
            return null;
        }

        $horas = $minutos / 60;

        return rtrim(rtrim(number_format($horas, 2, ',', ''), '0'), ',');
    }

    /* ------------------------- HELPERS/CONSULTAS ÚTEIS ------------------------ */

    /**
     * Lista cursos do aluno com informações de matrícula e contagem de aulas.
     */
    public static function getCursosByAlunoId($alunoId)
    {
        return self::query()
            ->select(
                'cursos.id',
                'matriculas.progresso_porcentagem',
                'matriculas.status',
                'matriculas.data_matricula'
            )
            ->join('matriculas', 'matriculas.curso_id', '=', 'cursos.id')
            ->where('matriculas.aluno_id', $alunoId)
            ->with(['categoria'])
            ->withCount('aulas as aulas_total') // usa o relacionamento aulas()
            ->orderByDesc('matriculas.data_matricula')
            ->get();
    }




    /**
     * (Opcional) Se quiser usar slug nas rotas: habilite e ajuste Route Model Binding.
     */
    // public function getRouteKeyName(): string
    // {
    //     return 'slug';
    // }
}
