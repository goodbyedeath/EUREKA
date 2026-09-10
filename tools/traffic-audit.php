<?php
/**
 * Request-budget audit.
 *
 * Every "site is down" incident on this install was HTTP 429, and every app-side cause was
 * found by reading code after the fact. This measures instead: it walks the front-end,
 * follows each repeating timer into the function that actually calls the network, and
 * prints a per-minute request budget.
 *
 *   /opt/alt/php83/usr/bin/php tools/traffic-audit.php
 *   /opt/alt/php83/usr/bin/php tools/traffic-audit.php --teams=25
 *   /opt/alt/php83/usr/bin/php tools/traffic-audit.php --web-ui     # count the retired web UI too
 *
 * Two rules were learned the hard way and are enforced below:
 *
 *   1. Code that nothing loads makes no requests. An earlier version of this tool ranked
 *      /ping as the single busiest endpoint on the site. /ping is called only from
 *      network-monitor.js, which no view includes, no entry imports, and which is absent
 *      from the built bundle. It has never made a request.
 *
 *   2. Reachability is not frequency. A 1s countdown that *can* reach /api/quiz/submit
 *      does so once, past an `if`, not sixty times a minute.
 *
 * It reads source, so it cannot see the Android client's traffic. See §APK below.
 */

$root   = dirname(__DIR__);
$teams  = 20;
$webUi  = false;
foreach ($argv as $arg) {
    if (preg_match('/^--teams=(\d+)$/', $arg, $m)) $teams = max(1, (int) $m[1]);
    if ($arg === '--web-ui') $webUi = true;
}

const MAX_HOPS = 3;
const KEYWORDS = ['if','for','while','switch','catch','function','return','typeof','await','else','do','with'];
/** Plumbing — never the answer to "what does this timer do?". */
const BUILTINS = ['clearInterval','clearTimeout','setTimeout','setInterval','console','parseInt','parseFloat','JSON','Object','Math','Date','String','Number','Array','Promise','fetch','require'];
/** A request to one of these leaves our rate limit alone. */
const FOREIGN  = ['TRACKER_API_BASE', 'tracker.questerra-series.com', 'tile.openstreetmap.org', 'openstreetmap', 'unpkg.com'];

/**
 * Who runs this file. The participant UI on the web is retired — the Android client is the
 * player-facing app now — so by default its timers are reported but not billed to the venue.
 */
function audienceOf(string $rel): string
{
    foreach ([
        '#^resources/views/kiosk/#'                    => 'kiosk',
        '#^resources/views/(admin|livewire/admin)/#'   => 'admin',
        '#^resources/views/(user|livewire/user)/#'     => 'team-web',
        '#^resources/js/components/(quest-locations|quiz-take|qr-scanner)\.js$#' => 'team-web',
    ] as $re => $who) {
        if (preg_match($re, $rel)) return $who;
    }
    return 'shared';
}

// ---------------------------------------------------------------- liveness

/** Every .js file reachable from a Vite entry by following imports. */
function liveJsFiles(string $root): array
{
    $config = @file_get_contents("$root/vite.config.js") ?: '';
    preg_match('/input:\s*\[(.*?)\]/s', $config, $m);
    preg_match_all('/[\'"]([^\'"]+\.js)[\'"]/', $m[1] ?? '', $e);

    $live = []; $queue = $e[1] ?? [];
    while ($queue) {
        $rel = array_shift($queue);
        if (isset($live[$rel])) continue;
        $abs = "$root/$rel";
        if (! is_file($abs)) continue;
        $live[$rel] = true;

        // Follow relative imports only; a package import cannot be one of our files.
        preg_match_all('/(?:import\s+[^;]*?from\s*|import\s*)[\'"](\.[^\'"]+)[\'"]/', file_get_contents($abs), $im);
        foreach ($im[1] as $spec) {
            $base = dirname($rel) . '/' . $spec;
            foreach ([$base, "$base.js", "$base/index.js"] as $cand) {
                $norm = preg_replace('#/\./#', '/', $cand);
                while (preg_match('#[^/]+/\.\./#', $norm)) $norm = preg_replace('#[^/]+/\.\./#', '', $norm, 1);
                if (is_file("$root/$norm")) { $queue[] = $norm; break; }
            }
        }
    }
    return $live;
}

// ---------------------------------------------------------------- parsing

/** Index of the matching close bracket, skipping strings and comments. */
function matchPair(string $s, int $open, string $oc, string $cc): int
{
    $depth = 0; $n = strlen($s); $q = null;
    for ($i = $open; $i < $n; $i++) {
        $c = $s[$i];
        if ($q !== null) {
            if ($c === '\\') { $i++; continue; }
            if ($c === $q) $q = null;
            continue;
        }
        if ($c === '"' || $c === "'" || $c === '`') { $q = $c; continue; }
        if ($c === '/' && $i + 1 < $n) {
            if ($s[$i+1] === '/') { $i = strpos($s, "\n", $i) ?: $n; continue; }
            if ($s[$i+1] === '*') { $e = strpos($s, '*/', $i); $i = $e === false ? $n : $e + 1; continue; }
        }
        if ($c === $oc) $depth++;
        elseif ($c === $cc) { $depth--; if ($depth === 0) return $i; }
    }
    return $n - 1;
}

/**
 * Replace every comment with spaces, keeping the string byte-for-byte the same length so
 * offsets and line numbers still line up.
 *
 * Not cosmetic: a prose comment in led.blade.php mentioning `updateData()` made this tool
 * follow the LED board into a function it never calls, and report a tracker-host poll as a
 * call to our own API.
 */
function blankComments(string $s): string
{
    $n = strlen($s); $out = $s; $q = null;
    for ($i = 0; $i < $n; $i++) {
        $c = $s[$i];
        if ($q !== null) {
            if ($c === '\\') { $i++; continue; }
            if ($c === $q) $q = null;
            continue;
        }
        if ($c === '"' || $c === "'" || $c === '`') { $q = $c; continue; }

        $end = null;
        if ($c === '/' && $i + 1 < $n && $s[$i + 1] === '/')     $end = strpos($s, "\n", $i) ?: $n;
        elseif ($c === '/' && $i + 1 < $n && $s[$i + 1] === '*') { $e = strpos($s, '*/', $i); $end = $e === false ? $n : $e + 2; }
        elseif (substr($s, $i, 4) === '{{--')                    { $e = strpos($s, '--}}', $i); $end = $e === false ? $n : $e + 4; }
        elseif (substr($s, $i, 4) === '<!--')                    { $e = strpos($s, '-->', $i); $end = $e === false ? $n : $e + 3; }
        if ($end === null) continue;

        for ($j = $i; $j < $end; $j++) if ($out[$j] !== "\n") $out[$j] = ' ';
        $i = $end - 1;
    }
    return $out;
}

/** Every function-ish body in a file, by name. */
function indexDefinitions(string $src): array
{
    $defs = [];
    if (! preg_match_all('/([A-Za-z_$][\w$]*)\s*\(/', $src, $m, PREG_OFFSET_CAPTURE)) return $defs;

    foreach ($m[1] as [$name, $nameAt]) {
        $paren = strpos($src, '(', $nameAt + strlen($name));
        $close = matchPair($src, $paren, '(', ')');
        if (! preg_match('/\G\s*(?:=>\s*)?\{/', $src, $b, 0, $close + 1)) continue;
        $brace = strpos($src, '{', $close);
        $body  = substr($src, $brace, matchPair($src, $brace, '{', '}') - $brace + 1);

        $label = preg_match('/([A-Za-z_$][\w$]*)\s*[:=]\s*(?:async\s+)?$/', substr($src, 0, $nameAt), $lm) ? $lm[1] : null;
        if (in_array($name, KEYWORDS, true)) {
            if ($label) $defs[$label] = $body;          // `foo: function () {}`
            continue;
        }
        $defs[$name] = $body;
        if ($label) $defs[$label] = $body;              // `foo: () => {}`
    }
    return $defs;
}

/**
 * Is the call to $name inside an `if`, `else` or `catch` block within $body?
 *
 * This is the difference between a poller and a clock. quiz-take.js runs a 1s countdown
 * that *can* reach /api/quiz/submit — but only through `else`, once per attempt.
 */
function callIsGuarded(string $body, string $name): bool
{
    $re = '/(?:this\.|[\w$]+\.)?' . preg_quote($name, '/') . '\s*\(/';
    if (! preg_match($re, $body, $m, PREG_OFFSET_CAPTURE)) return false;
    $at = $m[0][1];

    $stack = []; $q = null;
    for ($i = 0; $i < $at; $i++) {
        $c = $body[$i];
        if ($q !== null) { if ($c === '\\') { $i++; } elseif ($c === $q) { $q = null; } continue; }
        if ($c === '"' || $c === "'" || $c === '`') { $q = $c; continue; }
        if ($c === '{') $stack[] = $i;
        elseif ($c === '}') array_pop($stack);
    }
    foreach ($stack as $brace) {
        // Identify what opened this block by reading backwards from the `{`. Asking "does an
        // `if (` appear in the preceding window" is a different, wrong question: it called a
        // real 4/min GPS poller conditional because of an unrelated early return above it.
        $k = $brace - 1;
        while ($k >= 0 && ctype_space($body[$k])) $k--;
        if ($k < 0) continue;

        if ($body[$k] === ')') {                        // `if (…) {`, `catch (e) {`
            $depth = 0;
            for ($p = $k; $p >= 0; $p--) {
                if ($body[$p] === ')') $depth++;
                elseif ($body[$p] === '(') { $depth--; if ($depth === 0) break; }
            }
            if ($p < 0) continue;
            if (preg_match('/\b(if|catch)\s*$/', substr($body, 0, $p))) return true;
            continue;                                   // `foo(…) {`, `(a) => {` — not a guard
        }
        if (preg_match('/\belse\s*$/', substr($body, 0, $k + 1))) return true;
    }
    return false;
}

/** Returns [$direct, $conditional]: code reached without a guard, and code reached past one. */
function reach(string $body, array $defs, int $hops = MAX_HOPS, array $seen = []): array
{
    $direct = $body; $cond = '';
    if ($hops <= 0) return [$direct, $cond];

    $names = [];
    if (preg_match('/^\s*(?:this\.)?([A-Za-z_$][\w$]*)\s*$/', $body, $bare)) $names[] = $bare[1];
    if (preg_match_all('/(?:this\.|[\w$]+\.)?([A-Za-z_$][\w$]*)\s*\(/', $body, $m)) $names = array_merge($names, $m[1]);

    foreach (array_unique($names) as $name) {
        if (isset($seen[$name]) || in_array($name, KEYWORDS, true) || ! isset($defs[$name])) continue;
        $seen[$name] = true;
        [$d, $c] = reach($defs[$name], $defs, $hops - 1, $seen);
        if (callIsGuarded($body, $name)) $cond .= "\n" . $d . $c;
        else { $direct .= "\n" . $d; $cond .= $c; }
    }
    return [$direct, $cond];
}

/** [isNetwork, isForeign, endpoint] */
function classify(string $code): array
{
    if (! preg_match('/\bfetch\s*\(|\$wire\.|@this\.|axios|XMLHttpRequest|sendBeacon/', $code)) return [false, false, null];

    $endpoint = null; $ours = false; $foreign = false;
    if (preg_match_all('/fetch\s*\(\s*[`\'"]([^`\'"]+)/', $code, $u)) {
        foreach ($u[1] as $url) {
            if (str_starts_with($url, '/')) { $ours = true; $endpoint ??= strtok($url, '?'); }
            elseif (preg_match('#^https?://#', $url)) $foreign = true;
        }
    }
    foreach (FOREIGN as $h) if (str_contains($code, $h)) $foreign = true;
    if (preg_match('/\$wire\.|@this\./', $code)) { $ours = true; $endpoint ??= '/livewire/update'; }

    return [true, $foreign && ! $ours, $endpoint];
}

// ------------------------------------------------------------------- scan
$liveJs = liveJsFiles($root);

$files = [];
foreach (['resources/js', 'resources/views'] as $dir) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir")) as $f) {
        if ($f->isFile() && preg_match('/\.(js|blade\.php)$/', $f->getFilename())) $files[] = $f->getPathname();
    }
}
sort($files);

$rows = [];
$unbounded = [];
foreach ($files as $path) {
    $raw  = file_get_contents($path);
    $src  = blankComments($raw);                        // same offsets, no prose
    $rel  = str_replace("$root/", '', $path);
    $defs = indexDefinitions($src);
    $dead = str_ends_with($rel, '.js') && ! isset($liveJs[$rel]);
    $who  = audienceOf($rel);

    $off = 0;
    while (($at = strpos($src, 'setInterval', $off)) !== false) {
        $off   = $at + 11;
        $paren = strpos($src, '(', $at);
        if ($paren === false) continue;
        $close = matchPair($src, $paren, '(', ')');
        $args  = substr($src, $paren + 1, $close - $paren - 1);
        if (! preg_match('/,\s*(\d+)\s*$/s', $args, $m)) continue;
        $ms = (int) $m[1];
        if ($ms < 1) continue;

        $cb = trim(substr($args, 0, strrpos($args, ',')));
        [$directCode, $condCode] = reach($cb, $defs);
        [$net, $foreign, $endpoint] = classify($directCode);
        [$condNet, , $condEndpoint] = classify($condCode);

        $rows[] = [
            'file' => basename($rel), 'path' => $rel, 'who' => $who, 'dead' => $dead,
            'line' => substr_count(substr($src, 0, $at), "\n") + 1,
            'what' => $endpoint ?? 'inline',
            // Name the work, not the wrapper: `setInterval(function () { poll(); })` is a
            // call to poll(), and printing "function" tells the reader nothing.
            'via'  => (function () use ($cb) {
                if (preg_match('/^\s*(?:this\.)?([A-Za-z_$][\w$]*)\s*$/', $cb, $b)) return $b[1];
                if (preg_match_all('/(?:this\.|[\w$]+\.)?([A-Za-z_$][\w$]*)\s*\(/', $cb, $m)) {
                    foreach ($m[1] as $n) {
                        if (! in_array($n, KEYWORDS, true) && ! in_array($n, BUILTINS, true)) return $n;
                    }
                }
                return 'inline';
            })(),
            'ms' => $ms, 'network' => $net, 'foreign' => $foreign,
            // Not billed per tick if the request sits behind an `if`, or if the timer stops
            // itself — a countdown that clears its interval and then asks the server once,
            // at zero, makes one request however long it counted for.
            'conditional' => (! $net && $condNet) ? ($condEndpoint ?? 'a request')
                : (($net && preg_match('/clearInterval\s*\(/', $cb)) ? ($endpoint ?? 'a request') : null),
        ];
    }

    // --- sources with no interval to read -------------------------------
    //
    // A code review found `navigator.geolocation.watchPosition` posting to an unthrottled
    // /api/live/position on every GPS fix. This tool did not see it, because it only ever
    // looked for setInterval and wire:poll — and something that fires as fast as the sensor
    // delivers has no interval to divide by. Anything unbounded is worse than a fast poll,
    // so it gets named rather than skipped.
    foreach (['watchPosition', 'requestAnimationFrame'] as $api) {
        $p = 0;
        while (($hit = strpos($src, $api, $p)) !== false) {
            $p = $hit + strlen($api);
            $paren = strpos($src, '(', $hit);
            if ($paren === false) continue;
            $body = substr($src, $paren, matchPair($src, $paren, '(', ')') - $paren + 1);
            [$direct] = reach($body, $defs);
            [$net, $foreign, $endpoint] = classify($direct);
            if (! $net || $foreign) continue;

            $unbounded[] = [
                'path' => $rel, 'who' => $who,
                'line' => substr_count(substr($src, 0, $hit), "\n") + 1,
                'api'  => $api, 'what' => $endpoint ?? 'a request',
            ];
        }
    }

    foreach (explode("\n", $src) as $i => $line) {
        if (! preg_match('/wire:poll(?:\.(?:visible\.)?(\d+)(m?s))?/', $line, $m)) continue;
        $ms = isset($m[1]) && $m[1] !== '' ? (int) $m[1] * (($m[2] ?? 's') === 'ms' ? 1 : 1000) : 2500;
        $rows[] = [
            'file' => basename($rel), 'path' => $rel, 'who' => $who, 'dead' => false,
            'line' => $i + 1, 'what' => '/livewire/update',
            'via' => 'wire:poll' . (str_contains($line, '.visible') ? ' (visible)' : ''),
            'ms' => $ms, 'network' => true, 'foreign' => false, 'conditional' => null,
        ];
    }
}

$storms = [];
foreach ($files as $path) {
    if (! str_ends_with($path, '.blade.php')) continue;
    foreach (explode("\n", file_get_contents($path)) as $i => $line) {
        if (! preg_match('/wire:model\.live(?!\.debounce)/', $line)) continue;
        if (preg_match('/type="(color|range|number|text|date|search)"|<textarea/', $line, $t)) {
            $storms[] = [str_replace("$root/", '', $path), $i + 1, $t[1] ?? 'textarea'];
        }
    }
}

// ------------------------------------------------------------------ report
$rate = fn ($ms) => round(60000 / $ms, 1);
$billed = fn ($r) => $r['network'] && ! $r['foreign'] && ! $r['dead'] && empty($r['conditional']);

$ours = array_values(array_filter($rows, $billed));
usort($ours, fn ($a, $b) => $a['ms'] <=> $b['ms']);

echo "\n  EUREKA — request budget      " . date('Y-m-d H:i') . "\n";
echo "  " . str_repeat('=', 94) . "\n\n";
echo "  LIVE, ON OUR DOMAIN — fastest first\n\n";
printf("  %-38s %-10s %-20s %-17s %6s %7s\n", 'FILE:LINE', 'WHO', 'CALLS', 'ENDPOINT', 'EVERY', 'PER MIN');
echo "  " . str_repeat('-', 94) . "\n";

$byWho = ['kiosk' => 0.0, 'admin' => 0.0, 'team-web' => 0.0, 'shared' => 0.0];
foreach ($ours as $r) {
    $per = $rate($r['ms']);
    $byWho[$r['who']] += $per;
    printf("  %-38s %-10s %-20s %-17s %5ss %7s\n",
        substr($r['file'] . ':' . $r['line'], 0, 38), $r['who'],
        substr($r['via'], 0, 20), substr($r['what'], 0, 17), $r['ms'] / 1000, $per);
}
echo "  " . str_repeat('-', 94) . "\n";

$perTeam = $byWho['team-web'];
$fixed   = $byWho['kiosk'] + $byWho['admin'] + $byWho['shared'];

echo "\n  AT A VENUE — {$teams} teams behind one WiFi address\n\n";
printf("    kiosk screens              %7s /min\n", round($byWho['kiosk'], 1));
printf("    admin pages, while open    %7s /min\n", round($byWho['admin'], 1));
if ($webUi) {
    printf("    web participant UI         %7s /min  x %-3d = %s\n", round($perTeam, 1), $teams, round($perTeam * $teams, 1));
} elseif ($perTeam > 0) {
    printf("    web participant UI         %7s /min  x %-3d = %-6s  NOT COUNTED (retired; players use the APK)\n",
        round($perTeam, 1), $teams, round($perTeam * $teams, 1));
}
echo "    " . str_repeat('-', 56) . "\n";
$venue = $fixed + ($webUi ? $perTeam * $teams : 0);
printf("    TOTAL from that one IP     %7s /min\n\n", round($venue, 1));

echo "    limits: api 120/min per USER (token), kiosk 300/min per IP\n";
printf("    verdict: %s\n", $venue > 300
    ? '!! over the 300/min IP limit — the venue will start seeing 429'
    : 'within budget (' . round($venue / 300 * 100) . '% of the 300/min IP limit)');

if ($unbounded) {
    echo "\n  UNBOUNDED — fires as fast as the sensor or the screen, no interval to divide by\n\n";
    foreach ($unbounded as $u) {
        printf("    %-46s %-10s %-18s %s\n", $u['path'] . ':' . $u['line'], $u['who'], $u['api'] . '()', $u['what']);
    }
    echo "\n     No rate can be computed for these, which is exactly why they matter: a moving\n";
    echo "     phone emits a GPS fix roughly once a second. Give the handler its own throttle\n";
    echo "     in JS, and the endpoint a limiter, before an event.\n";
}
echo "\n  APK — not visible to this tool\n\n";
echo "    The Android client is the participant UI and its traffic is not in this repo. It\n";
echo "    authenticates with a Sanctum token, so throttle:api keys it per USER at 120/min —\n";
echo "    twenty teams no longer share one bucket the way twenty browsers on one WiFi did.\n";
echo "    The host and Cloudflare edge still count every request from the venue address, so\n";
echo "    the APK's own polling interval is the number that decides whether a venue is safe.\n";
echo "    Ask the client team for it; nothing here can measure it.\n";

if ($d = array_filter($rows, fn ($r) => $r['dead'] && ($r['network'] || $r['conditional']))) {
    echo "\n  DEAD CODE — no view loads it, no entry imports it, absent from the bundle\n\n";
    foreach ($d as $r) printf("    %-38s %-20s every %ss\n", $r['file'] . ':' . $r['line'], $r['via'], $r['ms'] / 1000);
    echo "\n     These make no requests at all. Counting them is how /ping was once ranked the\n";
    echo "     busiest endpoint on the site.\n";
}

if ($c = array_filter($rows, fn ($r) => ! empty($r['conditional']) && ! $r['dead'])) {
    echo "\n  NOT BILLED PER TICK — behind an `if`, or the timer clears itself\n\n";
    foreach ($c as $r) {
        printf("    %-38s %-20s %-17s tick %ss\n", $r['file'] . ':' . $r['line'],
            substr($r['via'], 0, 20), substr($r['conditional'], 0, 17), $r['ms'] / 1000);
    }
}

if ($f = array_filter($rows, fn ($r) => $r['network'] && $r['foreign'] && ! $r['dead'])) {
    echo "\n  ANOTHER HOST — costs us nothing\n\n";
    foreach ($f as $r) printf("    %-38s %-20s every %ss\n", $r['file'] . ':' . $r['line'], $r['via'], $r['ms'] / 1000);
}

echo "\n  wire:model.live WITHOUT debounce, on a control that fires while you drag or type\n\n";
if ($storms) {
    foreach ($storms as [$p, $ln, $t]) printf("    !! %-62s line %-6s %s\n", $p, $ln, $t);
} else {
    echo "    none\n";
}
echo "\n";
