<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\AnswerValidationService;

class Question extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'question',
        'type',
        'description',
        'game_name',
        'images',
        'options',
        'correct_answer',
        'points',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'images' => 'array',
            'points' => 'integer',
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

    public function gameAssessments(): HasMany
    {
        return $this->hasMany(GameAssessment::class);
    }

    /**
     * Check if a user's answer is correct for this question
     */
    public function checkAnswer(string $userAnswer): bool
    {
        // Brief questions are always considered "correct" as they are feedback
        if ($this->type === 'brief') {
            return true;
        }
        
        return AnswerValidationService::isAnswerCorrect($this, $userAnswer);
    }

    /**
     * Calculate points earned for a given answer
     */
    public function calculatePointsEarned(string $userAnswer): int
    {
        // Brief questions never give points
        if ($this->type === 'brief') {
            return 0;
        }
        
        return AnswerValidationService::calculatePointsEarned($this, $userAnswer);
    }

    /**
     * Validate answer format
     */
    public function validateAnswerFormat(string $userAnswer): array
    {
        return AnswerValidationService::validateAnswerFormat($this, $userAnswer);
    }

    /**
     * Validate that the question data is consistent (e.g., correct answer is in options for multiple choice)
     */
    public function validateQuestionDataIntegrity(): array
    {
        $errors = [];

        if ($this->type === 'multiple_choice') {
            $options = $this->options ?? [];
            
            if (empty($options) || count($options) < 2) {
                $errors[] = 'Multiple choice questions must have at least 2 options.';
            }

            if (!empty($this->correct_answer) && !in_array($this->correct_answer, $options)) {
                $errors[] = 'The correct answer must be one of the provided options.';
            }

            // Check for duplicate options
            $filteredOptions = array_filter($options, fn($option) => !empty(trim($option)));
            if (count($filteredOptions) !== count(array_unique($filteredOptions))) {
                $errors[] = 'All options must be unique.';
            }
        }

        // Brief questions don't need validation as they're just feedback
        if ($this->type === 'brief') {
            // No specific validation needed for brief questions
        }

        return $errors;
    }

    /**
     * Check if this question is scored
     */
    public function isScored(): bool
    {
        return $this->type !== 'brief';
    }

    /**
     * Check if this question is a feedback question
     */
    public function isFeedback(): bool
    {
        return $this->type === 'brief';
    }
}