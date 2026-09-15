<?php

namespace App\Services;

use App\Models\LoginCard;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generated team accounts with printed QR login cards (APK report #16, operator-approved 15 Sep).
 *
 * For an event with many teams: the admin generates N player accounts (no team yet) in one go and
 * prints one card each. The team scans its card in the app (POST /api/v1/auth/login-code) and goes
 * straight to team setup; the e-mail and password printed on the card remain a fallback.
 *
 * - The code is 192 random bits, looked up by sha256. Its QR payload is prefixed so it can never be
 *   mistaken for a station or START code.
 * - Reusable: a replaced phone logs in again with the same card.
 * - Rotate = new code and password (the old card and the account's tokens die) — for a lost card.
 * - Revoke = the account is switched off and logged out; unrevoke switches it back on.
 * - Archiving the session tombstones the codes, so an old card answers game_ended.
 */
class LoginCardService
{
    public const QR_PREFIX = 'EUREKA-LOGIN:';

    private const PASSWORD_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

    /** @return Collection<int, LoginCard> */
    public function generate(int $count, string $namePrefix, ?int $hours, User $admin): Collection
    {
        return DB::transaction(function () use ($count, $namePrefix, $hours, $admin) {
            $batch = now()->format('Ymd-His');
            $prefix = trim($namePrefix) !== '' ? trim($namePrefix) : 'Tim';
            $slug = Str::slug($prefix) ?: 'tim';
            $next = LoginCard::count();
            $cards = collect();

            for ($i = 1; $i <= $count; $i++) {
                $n = str_pad((string) ($next + $i), 2, '0', STR_PAD_LEFT);
                $code = $this->newCode();
                $password = $this->newPassword();

                $user = User::forceCreate([
                    'name' => "{$prefix} {$n}",
                    // Never delivered: the app sends no e-mail, and .invalid cannot resolve.
                    'email' => "{$slug}-{$n}-".Str::lower(Str::random(4)).'@questerra.invalid',
                    'password' => Hash::make($password),
                    'role' => 'user',
                    'is_active' => true,
                    'session_timeout' => $hours ? $hours * 3600 : null,
                ]);

                $cards->push(LoginCard::create([
                    'user_id' => $user->id,
                    'code_hash' => $this->hashFor($code),
                    'code_encrypted' => Crypt::encryptString($code),
                    'password_encrypted' => Crypt::encryptString($password),
                    'batch' => $batch,
                    'created_by' => $admin->id,
                ]));
            }

            return $cards;
        });
    }

    /** A lost card: new code and password; the old card and every session of the account die. */
    public function rotate(LoginCard $card): void
    {
        DB::transaction(function () use ($card) {
            $code = $this->newCode();
            $password = $this->newPassword();

            $card->update([
                'code_hash' => $this->hashFor($code),
                'code_encrypted' => Crypt::encryptString($code),
                'password_encrypted' => Crypt::encryptString($password),
            ]);
            $card->user->forceFill(['password' => Hash::make($password)])->save();
            $card->user->tokens()->delete();
        });
    }

    public function setRevoked(LoginCard $card, bool $revoked): void
    {
        DB::transaction(function () use ($card, $revoked) {
            $card->update(['revoked_at' => $revoked ? now() : null]);
            $card->user->forceFill(['is_active' => ! $revoked])->save();
            if ($revoked) {
                $card->user->tokens()->delete();
            }
        });
    }

    /** sha256 of the code, accepting the scanned QR payload or the bare code. */
    public function hashFor(string $raw): string
    {
        $raw = trim($raw);
        if (str_starts_with($raw, self::QR_PREFIX)) {
            $raw = substr($raw, strlen(self::QR_PREFIX));
        }

        return hash('sha256', $raw);
    }

    /** The card's QR as a PNG data URI, for the printable PDF. */
    public function qrDataUri(LoginCard $card, int $size = 360): string
    {
        $png = QrCode::format('png')->size($size)->margin(1)->errorCorrection('M')
            ->generate(self::QR_PREFIX.$card->plainCode());

        return 'data:image/png;base64,'.base64_encode((string) $png);
    }

    private function newCode(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');   // 192 bits, 32 chars
    }

    private function newPassword(int $length = 10): string
    {
        $out = '';
        $max = strlen(self::PASSWORD_ALPHABET) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= self::PASSWORD_ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
