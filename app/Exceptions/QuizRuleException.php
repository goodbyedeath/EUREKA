<?php

namespace App\Exceptions;

use Exception;

/**
 * A quiz rule the client broke — not a server fault.
 *
 * The API used to throw plain `\Exception` for expected states ("quiz has been submitted",
 * "game already assessed") and return them as HTTP 500 carrying the raw message. A native
 * client could only string-match on English prose to tell a normal double-submit from a real
 * outage, and internal messages leaked to the device. Each of these now carries a stable
 * `error` key and a 4xx, like every other refusal in the v1 contract.
 */
class QuizRuleException extends Exception
{
    public function __construct(
        public readonly string $errorKey,
        string $message,
        public readonly int $status = 409,
    ) {
        parent::__construct($message);
    }

    public static function submitted(): self
    {
        return new self('attempt_submitted', 'This attempt has already been submitted.', 409);
    }

    public static function attemptNotFound(): self
    {
        return new self('attempt_not_found', 'That quiz attempt does not exist, or belongs to someone else.', 404);
    }

    public static function questionNotFound(): self
    {
        return new self('question_not_found', 'That question is not part of this questionnaire.', 404);
    }

    public static function gameAlreadyAssessed(): self
    {
        return new self('game_already_assessed', 'This game has already been completed and assessed.', 409);
    }

    public static function timeExpired(): self
    {
        return new self('time_expired', 'The time limit for this quiz has passed.', 403);
    }
}
