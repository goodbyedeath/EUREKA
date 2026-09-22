<?php

namespace App\Services;

use App\Models\Questionnaire;
use App\Support\QuestionPayload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Questionnaires sealed for offline play: on the phone from the start line, readable only at the post.
 *
 * The operator wants the app to keep working with no signal (22 Sep), which means the questions must
 * be on the phone before the team walks out of coverage. But the manifest used to leave quiz content
 * out on purpose — "questionnaires are QR-gated, so shipping them ahead of time would hand the team
 * the whole hunt" — and that reason still holds. So each questionnaire travels encrypted, and its key
 * is the QR code printed at its post. A team that has not stood at the post has ciphertext; scanning
 * the code there opens it, with no network.
 *
 * The scheme, which the client implements exactly (build guide §10):
 *
 *   code    = the scanned QR string, trimmed — the same string qr/lookup matches on
 *   master  = PBKDF2-HMAC-SHA256(code, salt, ITERATIONS, 32 bytes)
 *   qr_hash = hex(HMAC-SHA256(master, "questerra/qr-id"))      lets the phone tell which one it scanned
 *   key     = HMAC-SHA256(master, "questerra/enc")             AES-256 key
 *   sealed  = AES-256-GCM(key, nonce 12 bytes, AAD "questerra/questionnaire/{id}"), tag appended
 *   asset   = nonce(12) ‖ ciphertext ‖ tag(16), AAD "questerra/asset/{id}/{ref}"
 *
 * The manifest never carries the code itself, only qr_hash — and qr_hash sits behind the same PBKDF2,
 * so it is no cheaper a guessing target than the ciphertext. That makes the scheme exactly as strong
 * as the code: the UUIDs this app generates are out of reach, a hand-typed "POS1" is not, which is why
 * Cek Kesiapan warns about short codes.
 *
 * The right answers are not in here at all: QuestionPayload drops them, as it does online.
 */
class OfflineQuestionnaireSeal
{
    public const ITERATIONS = 100000;

    public const VERSION = 1;

    /**
     * Public and deterministic: derived from APP_KEY so it needs no storage, is the same on every
     * request, and changes only if the key is rotated — which just means the next Sync re-seals.
     */
    public function salt(): string
    {
        return substr(hash('sha256', 'questerra/offline-salt|'.config('app.key'), true), 0, 16);
    }

    /** What the client needs to derive keys the same way. */
    public function parameters(): array
    {
        return [
            'version' => self::VERSION,
            'kdf' => 'PBKDF2-HMAC-SHA256',
            'iterations' => self::ITERATIONS,
            'salt' => base64_encode($this->salt()),
            'cipher' => 'AES-256-GCM',
        ];
    }

    /** The manifest row for one questionnaire, or null when it has no code to seal it with. */
    public function manifestEntry(Questionnaire $questionnaire): ?array
    {
        $code = trim((string) $questionnaire->qr_code);
        if ($code === '') {
            return null;
        }

        $master = $this->master($code);
        [$questions, $assets] = $this->content($questionnaire);

        $plaintext = json_encode([
            'title' => $questionnaire->title,
            'description' => $questionnaire->description,
            'questions' => $questions,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'id' => $questionnaire->id,
            'qr_hash' => bin2hex(hash_hmac('sha256', 'questerra/qr-id', $master, true)),
            // Enough to place the questionnaire on the map and plan the route before scanning —
            // nothing about what it asks.
            'venue_mode' => $questionnaire->venue_mode,
            'quest_location_id' => $questionnaire->quest_location_id,
            'game_location_id' => $questionnaire->game_location_id,
            'counts_toward_finish' => (bool) $questionnaire->counts_toward_finish,
            'time_limit' => $questionnaire->time_limit !== null ? (int) $questionnaire->time_limit : null,
            'start_date' => $questionnaire->start_date?->toDateString(),
            'end_date' => $questionnaire->end_date?->toDateString(),
            'question_count' => count($questions),
            'sealed' => $this->encrypt($this->key($master), $plaintext, "questerra/questionnaire/{$questionnaire->id}"),
            'assets' => collect(array_keys($assets))->map(fn ($ref) => [
                'ref' => $ref,
                'url' => route('api.v1.offline.questionnaire-asset', ['questionnaire' => $questionnaire->id, 'ref' => $ref]),
            ])->values()->all(),
        ];
    }

    /**
     * One picture of one questionnaire, encrypted with that questionnaire's key.
     *
     * @return string|null nonce ‖ ciphertext ‖ tag, or null when the ref is not one of its pictures
     */
    public function sealedAsset(Questionnaire $questionnaire, string $ref): ?string
    {
        $code = trim((string) $questionnaire->qr_code);
        [, $assets] = $this->content($questionnaire);
        $path = $assets[$ref] ?? null;

        if ($code === '' || $path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $sealed = $this->encrypt($this->key($this->master($code)), Storage::disk('public')->get($path),
            "questerra/asset/{$questionnaire->id}/{$ref}");

        return base64_decode($sealed['nonce']).base64_decode($sealed['ciphertext']);
    }

    /**
     * The questions as the app receives them online, with every picture replaced by an "asset:<ref>"
     * the phone resolves to its own decrypted copy — so no question's picture can be fetched in the
     * clear before the post.
     *
     * @return array{0: list<array>, 1: array<string, string>} questions, and ref => storage path
     */
    private function content(Questionnaire $questionnaire): array
    {
        $assets = [];
        $refFor = function (string $path) use (&$assets): string {
            $ref = array_search($path, $assets, true);
            if ($ref === false) {
                $ref = 'a'.(count($assets) + 1);
                $assets[$ref] = $path;
            }

            return $ref;
        };

        $questions = $questionnaire->questions()
            ->orderBy('order')
            ->get(QuestionPayload::COLUMNS)
            ->map(function ($question) use ($refFor) {
                $row = QuestionPayload::forApp($question);

                $row['image_urls'] = collect((array) ($question->images ?? []))
                    ->filter(fn ($p) => is_string($p) && $p !== '')
                    // A picture already hosted elsewhere cannot be sealed; it stays a plain URL.
                    ->map(fn ($p) => preg_match('#^https?://#i', $p) ? $p : 'asset:'.$refFor($p))
                    ->values()
                    ->all();
                $row['frame_url'] = $question->frame_path ? 'asset:'.$refFor($question->frame_path) : null;

                return $row;
            })
            ->values()
            ->all();

        return [$questions, $assets];
    }

    /** PBKDF2 is deliberately slow; each code's result is kept so a manifest does not pay it again. */
    private function master(string $code): string
    {
        $cacheKey = 'offline-seal:'.self::VERSION.':'.hash('sha256', $code.'|'.bin2hex($this->salt()));

        return base64_decode(Cache::rememberForever($cacheKey, fn () => base64_encode(
            hash_pbkdf2('sha256', $code, $this->salt(), self::ITERATIONS, 32, true)
        )));
    }

    private function key(string $master): string
    {
        return hash_hmac('sha256', 'questerra/enc', $master, true);
    }

    /** @return array{nonce: string, ciphertext: string} both base64; the tag is appended to the ciphertext */
    private function encrypt(string $key, string $plaintext, string $aad): array
    {
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);

        return ['nonce' => base64_encode($nonce), 'ciphertext' => base64_encode($ciphertext.$tag)];
    }
}
