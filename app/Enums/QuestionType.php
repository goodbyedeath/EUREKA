<?php

namespace App\Enums;

enum QuestionType: string
{
    case TEXT = 'text';
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';
    case FUN_GAME = 'fun_game';

    /**
     * Get all question type values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get question type display names
     */
    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text Answer',
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::TRUE_FALSE => 'True/False',
            self::FUN_GAME => 'Fun Game',
        };
    }

    /**
     * Get question type descriptions
     */
    public function description(): string
    {
        return match ($this) {
            self::TEXT => 'Users type their answer in a text field',
            self::MULTIPLE_CHOICE => 'Users select from predefined options',
            self::TRUE_FALSE => 'Users choose between True and False',
            self::FUN_GAME => 'Interactive game activity with manual assessment',
        };
    }

    /**
     * Check if this question type requires options
     */
    public function requiresOptions(): bool
    {
        return $this === self::MULTIPLE_CHOICE;
    }

    /**
     * Get validation rules for this question type
     */
    public function getValidationRules(): array
    {
        return match ($this) {
            self::TEXT => [
                'correct_answer' => 'required|string|max:1000',
            ],
            self::MULTIPLE_CHOICE => [
                'options' => 'required|array|min:2|max:8',
                'options.*' => 'required|string|max:200|distinct',
                'correct_answer' => 'required|string', // Custom validation needed in form
            ],
            self::TRUE_FALSE => [
                'correct_answer' => 'required|in:true,false',
            ],
            self::FUN_GAME => [
                'game_name' => 'required|string|max:200',
                'description' => 'required|string|max:2000',
                'images' => 'nullable|array|max:10',
                'images.*' => 'nullable|string',
            ],
        };
    }

    /**
     * Get default values for this question type
     */
    public function getDefaults(): array
    {
        return match ($this) {
            self::TEXT => [
                'options' => null,
                'correct_answer' => '',
            ],
            self::MULTIPLE_CHOICE => [
                'options' => ['', '', '', ''],
                'correct_answer' => '',
            ],
            self::TRUE_FALSE => [
                'options' => null,
                'correct_answer' => 'true',
            ],
            self::FUN_GAME => [
                'options' => null,
                'correct_answer' => '',
                'game_name' => '',
                'description' => '',
                'images' => [],
            ],
        };
    }
}