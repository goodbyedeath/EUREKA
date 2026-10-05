<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

/**
 * What this installation is actually wired to, read from the running app rather than from a .env someone
 * remembers editing.
 *
 * Written for phase 2 of SCALING-PLAN.md, where the whole change is configuration: cache, sessions, the
 * queue and — through the cache — the rate limiter stop touching MySQL. A change like that is invisible
 * until something states it, and "I set it in .env" is not the same as "the app is using it".
 *
 *   php artisan eureka:doctor
 */
class Doctor extends Command
{
    protected $signature = 'eureka:doctor {--json : machine-readable, for the deploy script}';

    protected $description = 'Report the live wiring: drivers, Redis, opcache, database, and what still writes to MySQL';

    public function handle(): int
    {
        $facts = $this->gather();

        if ($this->option('json')) {
            $this->line(json_encode($facts, JSON_PRETTY_PRINT));

            return $facts['healthy'] ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->line('<options=bold>EUREKA — live wiring</>');
        $this->newLine();

        $this->table(['', 'Value', ''], collect($facts['rows'])->map(fn ($r) => [
            $r['label'],
            $r['value'],
            match (true) {
                $r['fault'] => '<fg=red>FAIL</>',
                $r['ok'] === true => '<fg=green>ok</>',
                $r['ok'] === false => '<fg=yellow>look</>',
                default => '',
            },
        ])->all());

        if ($facts['notes']) {
            $this->newLine();
            foreach ($facts['notes'] as $note) {
                $this->line(' • '.$note);
            }
        }

        $this->newLine();

        return $facts['healthy'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Two different questions, which is why a row carries two flags.
     *
     * `ok` is "is this what the scaling plan asks for" — a MySQL cache store is a <fg=yellow>look</>,
     * because phase 2 wants Redis, but the shared host is deliberately staying on MySQL and the command
     * must not call that broken. `fault` is "something is actually wrong here", and only a fault moves
     * the exit code, so `./deploy.sh doctor` in the stack's health check fails on an unreachable Redis
     * and stays quiet about a host that was never meant to have one.
     *
     * @return array{rows:list<array{label:string,value:string,ok:?bool,fault:bool}>, notes:list<string>, healthy:bool}
     */
    private function gather(): array
    {
        $rows = [];
        $notes = [];
        $row = function (string $label, string $value, ?bool $ok = null, bool $fault = false) use (&$rows) {
            $rows[] = compact('label', 'value', 'ok', 'fault');
        };

        $row('PHP', PHP_VERSION, $php83 = version_compare(PHP_VERSION, '8.3', '>='), ! $php83);
        $row('opcache', $this->opcache(), function_exists('opcache_get_status') && @opcache_get_status(false) !== false);
        $row('environment', (string) config('app.env'), config('app.debug') === false);
        $row('debug', config('app.debug') ? 'ON' : 'off', ! config('app.debug'), (bool) config('app.debug') && config('app.env') === 'production');

        // The four that phase 2 is about.
        $cache = (string) config('cache.default');
        $session = (string) config('session.driver');
        $queue = (string) config('queue.default');
        $row('cache store', $cache, $cache !== 'database');
        $row('session driver', $session.($session === 'redis' ? ' ('.(config('session.connection') ?: 'default').')' : ''), $session !== 'database');
        $row('queue connection', $queue, $queue !== 'database' && $queue !== 'sync');
        $row('rate limiter', 'follows the cache store → '.$cache, $cache !== 'database');

        if ($cache === 'database') {
            $notes[] = '<fg=yellow>The cache — and so the rate limiter — is on MySQL.</> Every player request '
                .'then reads and writes the `cache` table: ~1,000 writes/s at 500 req/s, which is the first '
                .'wall and the one no number of cores moves (SCALING-PLAN.md §3a).';
        }
        if ($session === 'database') {
            $notes[] = 'Sessions are on MySQL. Only the admin and kiosk use them — players hold API tokens — '
                .'so this is survivable at this scale, but on the workstation stack they belong on the '
                .'non-evicting Redis (`SESSION_CONNECTION=persistent`).';
        }
        if ($queue === 'database') {
            $notes[] = 'The queue is on MySQL, so each worker polls a table on a timer and competes with '
                .'request traffic for the same database. Harmless while almost nothing is queued; it stops '
                .'being harmless when photo thumbnailing moves into jobs (phase 4).';
        }

        // Redis, per logical connection, because they are deliberately different instances. On a stack
        // that uses none of them — the shared host — an unreachable Redis is the expected state, not a
        // finding, and reporting it as a problem would train people to ignore this command.
        $usesRedis = in_array('redis', [$cache, $session, $queue], true)
            || config('queue.connections.'.$queue.'.driver') === 'redis';

        foreach (['default', 'cache', 'queue', 'persistent'] as $name) {
            if (! config("database.redis.$name")) {
                continue;
            }
            if (! $usesRedis) {
                $row("redis:$name", 'not used by this installation', null);

                continue;
            }
            $host = config("database.redis.$name.host").':'.config("database.redis.$name.port")
                .' db'.config("database.redis.$name.database");
            try {
                Redis::connection($name)->ping();
                $policy = $this->evictionPolicy($name);

                $evicts = $policy && $policy !== 'noeviction' && in_array($name, ['persistent', 'queue'], true);
                $row("redis:$name", $host.($policy ? "  [$policy]" : ''), ! $evicts, $evicts);

                if ($name === 'persistent' && $evicts) {
                    $notes[] = "<fg=yellow>redis:persistent evicts ($policy).</> Sessions live there; an admin "
                        .'logged out mid-event looks like an application bug.';
                }
                if ($name === 'queue' && $evicts) {
                    $notes[] = "<fg=yellow>redis:queue evicts ($policy).</> A job dropped to make room for a cache "
                        .'entry is work nobody knows is missing.';
                }
            } catch (\Throwable $e) {
                $row("redis:$name", $host.' — '.$this->short($e), false, true);
            }
        }

        try {
            DB::select('select 1');
            $row('database', config('database.default').' / '.DB::connection()->getDatabaseName(), true);
        } catch (\Throwable $e) {
            $row('database', $this->short($e), false, true);
        }

        // Is MySQL still being used as a cache or queue in practice, whatever the config says? Never a
        // fault — rows here mean MySQL is doing work Redis should, which is phase 2's subject rather
        // than a broken installation.
        foreach (['cache' => 'cache rows', 'sessions' => 'session rows', 'jobs' => 'queued jobs'] as $table => $label) {
            try {
                $n = DB::table($table)->count();
                $row("mysql $label", (string) $n, $n === 0 || $table === 'jobs');
            } catch (\Throwable) {
                // The table may not exist on a stack that never used it. That is a pass, not a failure.
            }
        }

        // A real round trip, not just configuration.
        try {
            $probe = 'doctor:'.bin2hex(random_bytes(4));
            Cache::put($probe, 'x', 10);
            $ok = Cache::get($probe) === 'x';
            $row('cache round trip', $ok ? 'write + read ok' : 'wrote but did not read back', $ok, ! $ok);
            Cache::forget($probe);
        } catch (\Throwable $e) {
            $row('cache round trip', $this->short($e), false, true);
        }

        try {
            $row('queue size (default)', (string) Queue::size(), true);
        } catch (\Throwable $e) {
            $row('queue size (default)', $this->short($e), false, true);
        }

        // Can the venue log in at all? The limiters are sized from config/venue.php, and a ceiling
        // below the arrival rate means our own code turns the event away in its first minute
        // (SCALING-PLAN.md §3e). Cheap to state, expensive to discover at the door.
        $players = (int) config('venue.players', 0);
        $doors = max(1, (int) config('venue.doors_minutes', 10));
        $needed = (int) ceil($players / $doors);
        $ceiling = (int) config('venue.logins.per_minute_per_address', 0);
        $row('venue logins',
            sprintf('%s players / %d min → %d per min needed, ceiling %d', number_format($players), $doors, $needed, $ceiling),
            $ceiling >= $needed * 2,
            $ceiling < $needed,
        );

        // Whose forwarded address is believed. Empty is correct on a LAN and wrong behind Cloudflare.
        $proxies = app(\App\Http\Middleware\TrustProxies::class)->proxies();
        $row('trusted proxies', $proxies === []
            ? 'none — the address a connection comes from is used as-is'
            : count($proxies).' range(s) from config/venue.php');

        // How many addresses logins have actually arrived from — the §3e premise, measured.
        $sources = app(\App\Services\LoginGuard::class)->sources(180);
        $row('login addresses (3h)', $sources['logins'] === 0
            ? 'no logins yet — /admin/kesiapan explains how to measure'
            : sprintf('%d login(s) from %d address(es), %d failed', $sources['logins'], $sources['addresses'], $sources['failures']));

        $row('scheduler heartbeat', $this->heartbeat());

        // Does APP_KEY still match the data? The one check worth having on a day the app is moved.
        //
        // Login cards keep the team's code and password `Crypt`-encrypted, and the offline seal derives
        // its salt from the key, so a server brought up with a *fresh* APP_KEY cannot reprint a single
        // card ("The MAC is invalid", permanently) and hands every phone a different salt, making the
        // sealed questionnaires they already cached unopenable. Both failures are silent until someone
        // tries. Decrypting one card takes a millisecond and turns that into a row on this table.
        [$keyValue, $keyOk, $keyFault] = $this->appKeyMatchesData();
        $row('APP_KEY vs encrypted data', $keyValue, $keyOk, $keyFault);

        $healthy = collect($rows)->every(fn ($r) => ! $r['fault']);

        return ['rows' => $rows, 'notes' => $notes, 'healthy' => $healthy];
    }

    /**
     * Try the key against something it actually encrypted.
     *
     * @return array{0:string, 1:?bool, 2:bool}  value, ok, fault
     */
    private function appKeyMatchesData(): array
    {
        try {
            $row = \App\Models\LoginCard::whereNotNull('code_encrypted')->first();
        } catch (\Throwable $e) {
            return [$this->short($e), null, false];
        }

        if (! $row) {
            // Nothing encrypted yet. Not a pass and not a failure — there is simply no evidence.
            return ['no login cards to check against', null, false];
        }

        try {
            $row->plainCode();

            return ['matches — login cards decrypt', true, false];
        } catch (\Throwable $e) {
            return [
                'WRONG KEY — login cards will not decrypt ('.class_basename($e).'). Restore the '
                    .'APP_KEY this data was written with; a new one cannot be undone.',
                false,
                true,
            ];
        }
    }

    private function opcache(): string
    {
        if (! function_exists('opcache_get_status')) {
            return 'extension not loaded';
        }

        $s = @opcache_get_status(false);
        if (! $s || empty($s['opcache_enabled'])) {
            // Almost always the truth here: opcache.enable_cli is off by design, and this command runs in
            // CLI. What matters for latency is the FPM setting, which the ini still tells us.
            return sprintf('off in this SAPI (%s) · ini opcache.enable=%s — FPM is what matters, check a web request',
                PHP_SAPI,
                ini_get('opcache.enable') ? '1' : '0',
            );
        }
        $hits = $s['opcache_statistics']['opcache_hit_rate'] ?? null;
        $mem = round(($s['memory_usage']['used_memory'] ?? 0) / 1048576);

        return sprintf('on · %d MB used%s · timestamps %s',
            $mem,
            $hits !== null ? ' · '.round($hits, 1).'% hits' : '',
            ini_get('opcache.validate_timestamps') ? 'validated' : 'frozen (immutable image)',
        );
    }

    private function evictionPolicy(string $connection): ?string
    {
        try {
            $info = Redis::connection($connection)->config('GET', 'maxmemory-policy');
            if (is_array($info)) {
                return $info['maxmemory-policy'] ?? ($info[1] ?? null);
            }
        } catch (\Throwable) {
            // CONFIG GET is disabled on some managed Redis. Not knowing is not a failure.
        }

        return null;
    }

    private function heartbeat(): string
    {
        $at = \App\Models\AppSetting::row('scheduler_heartbeat')?->value;
        $php = \App\Models\AppSetting::row('scheduler_php')?->value;
        if (! $at) {
            return 'never — the scheduler has not run';
        }
        $age = (int) now()->diffInSeconds(\Illuminate\Support\Carbon::parse($at), true);

        return sprintf('%s ago%s', $age < 120 ? $age.'s' : round($age / 60).'m', $php ? ' · '.$php : '');
    }

    private function short(\Throwable $e): string
    {
        return mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 70);
    }
}
