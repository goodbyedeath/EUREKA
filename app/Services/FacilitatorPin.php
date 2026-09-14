<?php

namespace App\Services;

use App\Exceptions\QuizRuleException;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One PIN per event, set by an admin, typed by the facilitator on the team's phone.
 *
 * The phone belongs to the team, so the PIN is the only thing standing between a team and its own
 * score. Hence: stored as a hash, fails closed when unset, and wrong guesses lock the account for
 * 15 minutes after 5 tries — a 4-digit PIN would otherwise fall to a script in minutes.
 */
class FacilitatorPin
{
    public const KEY = 'facilitator_pin_hash';
    public const MAX_TRIES = 5;
    public const LOCK_SECONDS = 900;

    public function isSet(): bool
    {
        return (bool) AppSetting::row(self::KEY)?->value;
    }

    public function updatedAt(): ?\Illuminate\Support\Carbon
    {
        return AppSetting::row(self::KEY)?->updated_at;
    }

    public function set(string $pin, int $adminId): void
    {
        AppSetting::put(self::KEY, Hash::make($pin), $adminId);
    }

    /** @throws QuizRuleException facilitator_pin_not_set | facilitator_pin_locked | invalid_facilitator_pin */
    public function verify(?string $pin, int $userId): void
    {
        $hash = AppSetting::row(self::KEY)?->value;
        if (! $hash) {
            throw new QuizRuleException('facilitator_pin_not_set', 'No facilitator PIN has been set for this event. Ask the event admin.', 409);
        }

        $key = 'facilitator-pin:'.$userId;
        if (RateLimiter::tooManyAttempts($key, self::MAX_TRIES)) {
            $wait = RateLimiter::availableIn($key);
            throw new QuizRuleException('facilitator_pin_locked', "Too many wrong PINs. Try again in {$wait} seconds.", 429, ['retry_after' => $wait]);
        }

        if (! is_string($pin) || $pin === '' || ! Hash::check($pin, $hash)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $left = max(0, self::MAX_TRIES - RateLimiter::attempts($key));
            throw new QuizRuleException('invalid_facilitator_pin', 'Wrong facilitator PIN.', 403, ['attempts_left' => $left]);
        }

        RateLimiter::clear($key);
    }
}
