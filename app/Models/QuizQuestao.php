<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestao extends Model
{

    protected $table = 'quiz_questoes';
    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
        'enunciado',
        'tipo',        // 'multipla' | 'dissertativa' (ajuste ao seu enum/uso)
        'pontuacao',   // pontos da questão
        'ordem',
        'id',
    ];

    protected $casts = [
        'pontuacao' => 'float',
        'ordem' => 'integer',
    ];

    /* ----------------------------- RELACIONAMENTOS ----------------------------- */

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function opcoes()
    {
        return $this->hasMany(QuizOpcao::class, 'questao_id')->orderBy('id');
    }

    public function respostas()
    {
        return $this->hasMany(QuizResposta::class, 'questao_id');
    }
}
