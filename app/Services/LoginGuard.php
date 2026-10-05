<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * What stops a credential spray without stopping a venue.
 *
 * SCALING-PLAN.md §3e: a per-address ceiling cannot tell 5,000 players apart from one script, because
 * at a venue they may all arrive from the same address. Guessing one account's password is capped per
 * *account* (`AppServiceProvider`, the `auth` limiter), which is venue-safe — 5,000 people are 5,000
 * buckets. What that leaves is someone working through a list of accounts from one place, and the
 * thing which actually separates them is not how many attempts they make but **how many of them
 * fail**.
 *
 * A room of teams produces almost no failures: a mistyped password, a creased card, a handful a
 * minute at worst. A stolen list produces nothing else. So this counts failures per address, and the
 * ceiling is a venue figure in `config/venue.php` rather than a number guessed here.
 *
 * It also records which addresses logins arrive from, because §3e's premise — "5,000 phones behind
 * one NAT address" — is a claim about the network that nothing in this app could check. Now
 * `/admin/kesiapan` can, before the event.
 */
class LoginGuard
{
    /** Window for the failure count, in seconds. One minute, matching the configured ceiling. */
    private const FAILURE_WINDOW = 60;

    /**
     * Too many wrong credentials from this address lately?
     *
     * @return array{retry_after:int}|null  null when it may proceed
     */
    public function sprayingFrom(string $ip): ?array
    {
        $ceiling = (int) config('venue.logins.failures_per_minute_per_address', 60);

        if ($ceiling <= 0) {
            return null;                       // switched off for an event that wants no such limit
        }

        if (! RateLimiter::tooManyAttempts($this->key($ip), $ceiling)) {
            return null;
        }

        return ['retry_after' => RateLimiter::availableIn($this->key($ip))];
    }

    /** A wrong password or an unknown card. Counted against the address, never against the account. */
    public function failed(string $ip): void
    {
        RateLimiter::hit($this->key($ip), self::FAILURE_WINDOW);
        $this->record($ip, failed: true);
    }

    /** A login that worked. Recorded only so the address count is the whole picture. */
    public function succeeded(string $ip): void
    {
        $this->record($ip, failed: false);
    }

    /**
     * One row per address, counting the logins — not one row per login.
     *
     * Deliberately not a model and not transactional: this is bookkeeping beside a login, and a
     * failure to write it must never be the reason somebody cannot get in. Hence the try/catch.
     */
    private function record(string $ip, bool $failed): void
    {
        if ($ip === '') {
            return;
        }

        try {
            $now = now();

            $updated = DB::table('login_sources')->where('ip', $ip)->update([
                'hits' => DB::raw('hits + 1'),
                'failures' => DB::raw('failures + '.($failed ? 1 : 0)),
                'last_seen_at' => $now,
            ]);

            if ($updated === 0) {
                // insertOrIgnore, so two phones arriving in the same millisecond do not collide on
                // the unique index and turn a login into a 500.
                DB::table('login_sources')->insertOrIgnore([
                    'ip' => $ip,
                    'hits' => 1,
                    'failures' => $failed ? 1 : 0,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            }
        } catch (\Throwable) {
            // Never block a login over a counter.
        }
    }

    /**
     * What the addresses say about the network, for `/admin/kesiapan`.
     *
     * @return array{addresses:int, logins:int, failures:int, busiest:?object, since:string}
     */
    public function sources(int $minutes = 60): array
    {
        $since = now()->subMinutes($minutes);

        $rows = DB::table('login_sources')->where('last_seen_at', '>=', $since);

        return [
            'addresses' => (clone $rows)->count(),
            'logins' => (int) (clone $rows)->sum('hits'),
            'failures' => (int) (clone $rows)->sum('failures'),
            'busiest' => (clone $rows)->orderByDesc('hits')->first(),
            'since' => $since->toDateTimeString(),
        ];
    }

    /**
     * The reading of it, in the crew's terms. This is the §3e question answered.
     *
     * @return array{state:string, headline:string, detail:string}
     */
    public function verdict(int $minutes = 60): array
    {
        $s = $this->sources($minutes);
        $ceiling = (int) config('venue.logins.per_minute_per_address', 1500);
        $players = (int) config('venue.players', 5000);
        $doors = max(1, (int) config('venue.doors_minutes', 10));
        $needed = (int) ceil($players / $doors);

        if ($s['logins'] === 0) {
            return [
                'state' => 'unknown',
                'headline' => 'Belum ada login untuk diukur',
                'detail' => "Event ini dihitung untuk $players pemain dalam $doors menit — sekitar "
                    ."$needed login per menit. Ceiling per alamat: $ceiling/menit. Suruh beberapa HP "
                    .'login dari WiFi venue, lalu lihat lagi: yang penting bukan berapa login, tapi '
                    .'dari berapa alamat.',
            ];
        }

        // One address for everything is the §3e case, and no per-address limit can work in it.
        if ($s['addresses'] <= 2 && $s['logins'] >= 10) {
            return [
                'state' => 'shared',
                'headline' => "{$s['logins']} login dari hanya {$s['addresses']} alamat",
                'detail' => 'Server melihat semua HP sebagai satu alamat — entah NAT di router, entah '
                    .'proxy yang alamatnya terbaca sebagai alamat klien. Artinya **setiap batas '
                    ."per-alamat dipakai bersama seluruh event**. Ceiling sekarang $ceiling/menit dan "
                    ."event ini butuh ~$needed/menit, jadi masih lolos — tapi tidak ada lagi "
                    .'perlindungan per-HP. Kalau ini di Docker: cek `userland-proxy` di daemon, '
                    .'karena itu menukar alamat klien dengan gateway bridge.',
            ];
        }

        if ($s['addresses'] < max(3, (int) ($s['logins'] / 20))) {
            return [
                'state' => 'few',
                'headline' => "{$s['logins']} login dari {$s['addresses']} alamat",
                'detail' => 'Jauh lebih sedikit alamat daripada HP. Mungkin benar (beberapa VLAN '
                    .'di-NAT), mungkin juga proxy. Kalau tiap HP seharusnya punya alamat sendiri, '
                    .'periksa routing di venue sebelum hari-H.',
            ];
        }

        return [
            'state' => 'per_device',
            'headline' => "{$s['logins']} login dari {$s['addresses']} alamat",
            'detail' => 'Tiap HP terlihat dengan alamatnya sendiri, jadi batas per-alamat benar-benar '
                ."per-HP. Ceiling $ceiling/menit tidak akan tersentuh. "
                .($s['failures'] > 0 ? "{$s['failures']} login gagal dalam rentang ini." : 'Tidak ada login gagal.'),
        ];
    }

    private function key(string $ip): string
    {
        return 'login-fail:'.$ip;
    }

    /** Clear the failure count for one address — the crew unblocking a booth that got stuck. */
    public function clear(string $ip): void
    {
        RateLimiter::clear($this->key($ip));
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    public function busiest(int $limit = 10, int $minutes = 60)
    {
        return DB::table('login_sources')
            ->where('last_seen_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderByDesc('hits')
            ->limit($limit)
            ->get();
    }
}
