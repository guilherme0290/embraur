<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{

    protected $table = 'quizzes';
    public $timestamps = false;

    protected $fillable = [
        'curso_id',
        'modulo_id',
        'titulo',
        'descricao',
        'escopo',            // ex.: 'modulo' | 'curso'
        'correcao_manual',   // bool
        'ativo',             // bool
    ];

    protected $casts = [
        'correcao_manual' => 'boolean',
        'ativo'           => 'boolean',
    ];

    /* ----------------------------- RELACIONAMENTOS ----------------------------- */

    public function curso()
    {
        return $this->belongsTo(Cursos::class, 'curso_id');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulos::class, 'modulo_id');
    }

    public function questoes()
    {
        return $this->hasMany(QuizQuestao::class, 'quiz_id')->orderBy('ordem')->orderBy('id');
    }

    public function tentativas()
    {
        return $this->hasMany(QuizTentativa::class, 'quiz_id')->orderByDesc('id');
    }
}
