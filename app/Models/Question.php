<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'points',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function checkAnswer($userAnswer)
    {
        if ($this->type === 'true_false') {
            return strtolower($this->correct_answer) === strtolower($userAnswer);
        }
        
        return strtolower(trim($this->correct_answer)) === strtolower(trim($userAnswer));
    }
}