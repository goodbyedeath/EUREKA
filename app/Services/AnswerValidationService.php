<?php

namespace App\Services;

use App\Models\Question;
use App\Enums\QuestionType;

class AnswerValidationService
{
    /**
     * Check if a user's answer is correct for a given question
     */
    public static function isAnswerCorrect(Question $question, string $userAnswer): bool
    {
        if (empty(trim($userAnswer))) {
            return false;
        }

        $correctAnswer = $question->correct_answer;
        
        if (empty($correctAnswer)) {
            return false;
        }

        $questionType = QuestionType::tryFrom($question->type);
        
        return match ($questionType) {
            QuestionType::MULTIPLE_CHOICE => self::validateMultipleChoice($question, $userAnswer, $correctAnswer),
            QuestionType::TRUE_FALSE => self::validateTrueFalse($userAnswer, $correctAnswer),
            QuestionType::TEXT => self::validateText($userAnswer, $correctAnswer),
            default => false,
        };
    }

    /**
     * Validate multiple choice answers
     */
    private static function validateMultipleChoice(Question $question, string $userAnswer, string $correctAnswer): bool
    {
        // Validate that the user's answer is a valid option
        $validOptions = $question->options ?? [];
        if (!in_array($userAnswer, $validOptions)) {
            return false;
        }

        // Validate that the correct answer is also in the options (data integrity check)
        if (!in_array($correctAnswer, $validOptions)) {
            \Log::warning("Question {$question->id} has correct answer '{$correctAnswer}' that is not in options: " . json_encode($validOptions));
            return false;
        }

        // Check if the answer matches the correct answer
        return trim($userAnswer) === trim($correctAnswer);
    }

    /**
     * Validate true/false answers
     */
    private static function validateTrueFalse(string $userAnswer, string $correctAnswer): bool
    {
        $userBool = self::convertToBoolean($userAnswer);
        $correctBool = self::convertToBoolean($correctAnswer);
        
        return $userBool === $correctBool;
    }

    /**
     * Validate text answers with support for multiple acceptable answers
     */
    private static function validateText(string $userAnswer, string $correctAnswer): bool
    {
        // Check for exact match first
        if (strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer))) {
            return true;
        }

        // Check for multiple acceptable answers (comma-separated)
        $acceptableAnswers = array_map('trim', explode(',', strtolower($correctAnswer)));
        return in_array(strtolower(trim($userAnswer)), $acceptableAnswers);
    }

    /**
     * Convert string to boolean value
     */
    private static function convertToBoolean(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['true', '1', 'yes', 'y']);
    }

    /**
     * Calculate points earned for a question
     */
    public static function calculatePointsEarned(Question $question, string $userAnswer): int
    {
        return self::isAnswerCorrect($question, $userAnswer) ? $question->points : 0;
    }

    /**
     * Validate answer format based on question type
     */
    public static function validateAnswerFormat(Question $question, string $userAnswer): array
    {
        $errors = [];
        $questionType = QuestionType::tryFrom($question->type);

        switch ($questionType) {
            case QuestionType::MULTIPLE_CHOICE:
                $validOptions = $question->options ?? [];
                if (!in_array($userAnswer, $validOptions)) {
                    $errors[] = 'Invalid option selected';
                }
                break;

            case QuestionType::TRUE_FALSE:
                $validValues = ['true', 'false', '1', '0', 'yes', 'no', 'y', 'n'];
                if (!in_array(strtolower(trim($userAnswer)), $validValues)) {
                    $errors[] = 'Invalid true/false value';
                }
                break;

            case QuestionType::TEXT:
                if (strlen($userAnswer) > 1000) {
                    $errors[] = 'Answer exceeds maximum length of 1000 characters';
                }
                break;

            default:
                $errors[] = 'Unsupported question type';
                break;
        }

        return $errors;
    }

    /**
     * Sanitize user answer for storage
     */
    public static function sanitizeAnswer(string $userAnswer): string
    {
        // Remove excessive whitespace and sanitize for XSS
        $sanitized = trim($userAnswer);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }
}