<?php

namespace App\Console\Commands;

use App\Models\PostArrival;
use App\Services\TapService;
use Illuminate\Console\Command;

/**
 * Read a gateway's serial output and turn each line into an arrival.
 *
 * This is the contract between the two halves of §6, and it is deliberately the dullest interface
 * available: the gateway prints one JSON object per line at 115200 baud over USB, and this reads
 * lines. No IP, no WiFi, no TLS, nothing to knock off the air (SCALING-PLAN.md §6) — the gateway sits
 * beside the server, so its cable is one metre.
 *
 *   php artisan eureka:tap-listen /dev/ttyUSB0          # one gateway
 *   php artisan eureka:tap-listen /dev/ttyUSB1 --gateway=2
 *   cat frames.jsonl | php artisan eureka:tap-listen -  # a replay, or the bench harness
 *
 * Two gateways are the point, not a luxury: one gateway is one device that can silence fifteen
 * booths. Run two instances; both write the same arrivals and the unique `(node_id, seq)` makes the
 * second one a no-op. That is also why this needs no coordination of its own.
 *
 * A line that cannot be parsed is reported and skipped. Nothing here exits on bad input — a reader
 * with a loose connection must not be able to stop the listener for the other fourteen.
 */
class TapListen extends Command
{
    protected $signature = 'eureka:tap-listen
        {device : the serial device, e.g. /dev/ttyUSB0 — or "-" to read standard input}
        {--gateway=1 : which gateway this is, when the frames do not say}
        {--baud=115200 : serial speed, matching the firmware}
        {--quiet-taps : only print refusals and faults}';

    protected $description = 'Read tap frames from a LoRa gateway over serial and open posts';

    public function handle(TapService $taps): int
    {
        $device = (string) $this->argument('device');

        $handle = $device === '-' ? STDIN : $this->openSerial($device);

        if ($handle === false) {
            $this->error("Cannot open $device. Is the gateway plugged in, and is this user in the dialout group?");

            return self::FAILURE;
        }

        $this->line(sprintf('<fg=green>Listening</> on %s as gateway %s. Ctrl-C to stop.',
            $device, $this->option('gateway')));
        $this->newLine();

        $seen = 0;
        $bad = 0;

        while (! feof($handle)) {
            $line = fgets($handle);

            if ($line === false) {
                // A serial read can simply time out; standard input ending is the real finish.
                if ($device === '-') {
                    break;
                }

                usleep(50_000);

                continue;
            }

            $arrival = $this->consume($line, $taps);

            if ($arrival === null) {
                continue;
            }

            if ($arrival === false) {
                $bad++;

                continue;
            }

            $seen++;
        }

        if ($handle !== STDIN) {
            fclose($handle);
        }

        $this->newLine();
        $this->line("  $seen frame(s) handled".($bad > 0 ? ", $bad unusable" : ''));

        return self::SUCCESS;
    }

    /**
     * One line from the gateway.
     *
     * Separate from the read loop so it can be tested without a serial port or a subprocess — which
     * matters here, because `exec()` is disabled on the shared host and the obvious way to test a
     * console command is to run one.
     *
     * @return PostArrival|null|false  the arrival; null for a line that is not a frame; false when it
     *                                 looked like one and could not be used
     */
    public function consume(string $line, TapService $taps): PostArrival|null|false
    {
        $line = trim($line);

        if ($line === '') {
            return null;
        }

        if (! str_starts_with($line, '{')) {
            // The firmware prints boot banners and diagnostics too; those are not frames.
            $this->line('  <fg=gray>'.mb_substr($line, 0, 110).'</>');

            return null;
        }

        $frame = json_decode($line, true);

        if (! is_array($frame) || ! isset($frame['node'], $frame['seq'])) {
            $this->line('  <fg=yellow>unparsed</> '.mb_substr($line, 0, 100));

            return false;
        }

        $frame['gw'] ??= (int) $this->option('gateway');

        try {
            $arrival = $taps->record($frame);
            $this->report($arrival);

            return $arrival;
        } catch (\Throwable $e) {
            $this->line('  <fg=red>failed</> '.$e->getMessage());

            return false;
        }
    }

    /**
     * Put the port in a sane mode, then read it as a file.
     *
     * `stty` rather than a PHP serial extension: there is no such extension in a standard build, and
     * the raw mode this needs is three flags. `-F` is the GNU spelling; BSD/macOS wants `-f`, which
     * is why a failure here says what to check rather than pretending it worked.
     */
    private function openSerial(string $device)
    {
        if (! file_exists($device)) {
            return false;
        }

        $baud = (int) $this->option('baud');

        // `stty` rather than a PHP serial extension, which no standard build has. Guarded because
        // `exec` is disabled on some hosts — including the shared host this repository lives on — and
        // a missing stty is not fatal: many USB adapters are already in raw mode at the right speed.
        if (function_exists('exec')) {
            @exec(sprintf('stty -F %s %d raw -echo -echoe -echok -crtscts 2>&1', escapeshellarg($device), $baud), $out, $code);

            if ($code !== 0) {
                $this->warn('  stty did not accept the port: '.implode(' ', (array) $out));
                $this->warn('  Continuing anyway — some adapters are already in raw mode.');
            }
        } else {
            $this->warn("  exec() is disabled here, so the port was not configured. Run once by hand:");
            $this->warn("    stty -F $device $baud raw -echo");
        }

        $handle = @fopen($device, 'r');

        if ($handle !== false) {
            // Non-blocking, so Ctrl-C is honoured instead of hanging in a read on a quiet night.
            stream_set_blocking($handle, false);
            stream_set_timeout($handle, 1);
        }

        return $handle;
    }

    /** One line per frame, coloured by what it did. The crew watches this during setup. */
    private function report(PostArrival $arrival): void
    {
        if ($arrival->outcome === PostArrival::HEARTBEAT) {
            if (! $this->option('quiet-taps')) {
                $this->line(sprintf('  <fg=gray>node %-3d heartbeat  %s  rssi %s</>',
                    $arrival->node_id,
                    $arrival->vbat_mv ? round($arrival->vbat_mv / 1000, 2).' V' : '— V',
                    $arrival->rssi ?? '—'));
            }

            return;
        }

        $duplicate = ! $arrival->wasRecentlyCreated;
        $colour = match (true) {
            $duplicate => 'gray',
            $arrival->worked() => 'green',
            $arrival->outcome === PostArrival::BAD_SIGNATURE => 'red',
            default => 'yellow',
        };

        if ($duplicate && $this->option('quiet-taps')) {
            return;
        }

        $this->line(sprintf('  <fg=%s>node %-3d seq %-6d %-14s %-28s</>%s',
            $colour,
            $arrival->node_id,
            $arrival->seq,
            $arrival->uid ?? '—',
            $arrival->label(),
            $duplicate ? ' <fg=gray>(already recorded — nothing done)</>' : '',
        ));
    }
}
