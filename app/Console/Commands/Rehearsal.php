<?php

namespace App\Console\Commands;

use App\Models\GameLocationUnlock;
use App\Models\PostArrival;
use App\Models\RaceSession;
use App\Models\RfidTag;
use App\Models\TapNode;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Did the rehearsal actually happen, and did the drills prove what they were supposed to?
 *
 * SCALING-PLAN.md §7: *"The crew has run the failure, not read about it."* The trouble with a
 * rehearsal is that it is remembered rather than recorded — everyone agrees it went fine, and nobody
 * can say afterwards whether the queued taps really arrived with their own timestamps or whether
 * anyone ever restored the backup.
 *
 * So this looks for the *evidence* each drill leaves behind, in the data, and says what is missing.
 * It proves nothing on its own — it reads what the rehearsal wrote. `REHEARSAL.md` is the run sheet;
 * this is the mark sheet.
 *
 *   php artisan eureka:rehearsal
 *   php artisan eureka:rehearsal --hours=24
 */
class Rehearsal extends Command
{
    protected $signature = 'eureka:rehearsal {--hours=12 : how far back the rehearsal was}';

    protected $description = 'Check the evidence each rehearsal drill leaves behind';

    private int $pass = 0;

    private int $todo = 0;

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $since = now()->subHours($hours);

        $this->newLine();
        $this->line(sprintf('<options=bold>Rehearsal check</> — evidence from the last %d hour(s)', $hours));
        $this->newLine();

        $this->section('Authoring');
        $this->setup();

        $this->section('The tap system');
        $this->taps($since);

        $this->section('Drill: the gateway was pulled mid-run');
        $this->queueDrill($since);

        $this->section('Drill: a restore from backup');
        $this->restoreDrill();

        $this->section('The box itself');
        $this->box();

        $this->newLine();
        $this->line($this->todo === 0
            ? "  <fg=green>Everything the rehearsal should have left behind is there.</> ($this->pass checks)"
            : "  <fg=yellow>$this->todo of ".($this->pass + $this->todo).' checks have no evidence yet.</> Run those drills before the event.');
        $this->newLine();

        // Deliberately always 0: this is a readout for a person, not a gate in a pipeline, and a
        // rehearsal that is half done is a normal state on the way to one that is done.
        return self::SUCCESS;
    }

    private function setup(): void
    {
        $teams = Team::count();
        $this->ok('teams exist', $teams > 0, "$teams team(s)");

        $withoutAccounts = Team::whereDoesntHave('users')->count();
        $this->ok('every team has a login account', $teams > 0 && $withoutAccounts === 0, match (true) {
            $teams === 0 => 'no teams yet',
            $withoutAccounts > 0 => "$withoutAccounts without",
            default => 'all of them',
        });

        $nodes = TapNode::where('is_active', true)->count();
        $unplaced = TapNode::where('is_active', true)->whereNull('game_location_id')->count();
        $this->ok('every active reader stands at a post', $nodes > 0 && $unplaced === 0,
            $nodes === 0 ? 'no readers registered' : ($unplaced > 0 ? "$unplaced not placed" : "$nodes placed"));

        $tags = RfidTag::whereNotNull('team_id')->count();
        $this->ok('every team has a card', $teams > 0 && $tags >= $teams, "$tags card(s) for $teams team(s)");

        $noKey = TapNode::whereNull('node_key')->count();
        $this->ok('every reader has its HMAC key', $nodes > 0 && $noKey === 0, match (true) {
            $nodes === 0 => 'no readers yet',
            $noKey > 0 => "$noKey without a key",
            default => 'all keyed',
        });
    }

    private function taps(\Illuminate\Support\Carbon $since): void
    {
        $arrivals = PostArrival::where('received_at', '>=', $since);
        $total = (clone $arrivals)->count();

        $this->ok('taps were made', $total > 0, "$total arrival(s)");

        $opened = (clone $arrivals)->where('outcome', PostArrival::OPENED)->count();
        $this->ok('a tap opened a post', $opened > 0, "$opened opened");

        // The unique key makes this impossible, which is exactly why it is worth asserting: if it
        // ever fails, the constraint is gone and every retry is a second arrival.
        $dupes = DB::table('post_arrivals')
            ->selectRaw('node_id, seq, COUNT(*) as n')
            ->groupBy('node_id', 'seq')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->ok('no tap was recorded twice', $dupes === 0, $dupes > 0 ? "$dupes duplicated" : 'none');

        $heard = (clone $arrivals)->distinct()->count('node_id');
        $active = TapNode::where('is_active', true)->count();
        $this->ok('every reader was heard from', $active > 0 && $heard >= $active,
            $active === 0 ? 'no readers yet' : "$heard of $active");

        $gateways = (clone $arrivals)->whereNotNull('gateway')->distinct()->count('gateway');
        $this->ok('both gateways were listening', $gateways >= 2,
            $gateways === 0 ? 'none reported' : "$gateways gateway(s)");

        $batteries = TapNode::whereNotNull('vbat_mv')->count();
        $flat = TapNode::get()->filter(fn (TapNode $n) => $n->batteryLow())->count();
        $this->ok('readers reported their battery', $batteries > 0, "$batteries reporting");
        $this->ok('no reader is on a flat battery', $flat === 0, $flat > 0 ? "$flat low" : 'all healthy');

        $spoofed = (clone $arrivals)->where('hmac_ok', false)->count();
        $this->ok('no frame arrived with a bad signature', $spoofed === 0,
            $spoofed > 0 ? "$spoofed — look at these" : 'none');
    }

    /**
     * The drill: unplug a gateway, make taps, plug it back in. What it must leave behind is arrivals
     * whose `tapped_at` is well before `received_at` — the node's clock, not the server's.
     */
    private function queueDrill(\Illuminate\Support\Carbon $since): void
    {
        $late = PostArrival::where('received_at', '>=', $since)
            ->whereNotNull('tapped_at')
            ->whereRaw('TIMESTAMPDIFF(SECOND, tapped_at, received_at) > 60')
            ->orderBy('id')
            ->get(['id', 'node_id', 'seq', 'tapped_at', 'received_at']);

        $this->ok('taps arrived late, carrying their own timestamps', $late->count() > 0,
            $late->count() > 0
                ? $late->count().' arrival(s), the longest delayed '
                    .(int) $late->max(fn ($a) => $a->tapped_at->diffInSeconds($a->received_at)).'s'
                : 'no evidence of a queue flush — unplug a gateway, tap, plug it back in');

        if ($late->count() > 0) {
            // In order, as the firmware is told to flush them. Out of order is not a fault on this
            // side, but it is worth seeing during a rehearsal.
            //
            // Ordered by `id`, not by `received_at`: a flush is a burst, so twenty arrivals share one
            // `received_at` second and sorting by it compares an arbitrary permutation. The primary
            // key is the insertion counter, which is exactly "the order the server took them in".
            $perNode = $late->groupBy('node_id')->map(function ($rows) {
                $seqs = $rows->sortBy('id')->pluck('seq')->values()->all();

                return $seqs === collect($seqs)->sort()->values()->all();
            });

            $this->ok('and in the order they were taken', $perNode->every(fn ($ordered) => $ordered),
                $perNode->every(fn ($o) => $o) ? 'in order' : 'some arrived out of order');
        }
    }

    /**
     * The drill: restore a backup onto the box. Evidence is awkward — a restore leaves no row saying
     * "I was restored" — so this checks what can be checked: that backups exist and are recent
     * enough to be worth restoring.
     */
    private function restoreDrill(): void
    {
        // Looked for where docker/backup.sh writes them, and beside the app for a non-Docker host.
        $dirs = array_filter([base_path('docker/backups'), base_path('backups'), storage_path('backups')], 'is_dir');

        $files = collect($dirs)->flatMap(fn ($d) => glob($d.'/eureka-*.sql.gz') ?: [])
            ->sortByDesc(fn ($f) => filemtime($f))
            ->values();

        $this->ok('a backup exists', $files->isNotEmpty(),
            $files->isEmpty() ? 'none found — run docker/backup.sh' : $files->count().' in '.implode(', ', array_map('basename', $dirs)));

        if ($files->isNotEmpty()) {
            $newest = $files->first();
            $age = (int) round((time() - filemtime($newest)) / 60);
            $this->ok('and it is recent', $age <= 24 * 60,
                basename($newest).', '.($age < 120 ? "$age min old" : round($age / 60).' h old'));

            $size = filesize($newest);
            $this->ok('and not suspiciously small', $size > 20_000, number_format($size / 1024, 0).' KB');

            $storage = preg_replace('/\.sql\.gz$/', '-storage.tgz', $newest);
            $this->ok('with its storage archive beside it', is_file($storage),
                is_file($storage) ? number_format(filesize($storage) / 1048576, 1).' MB' : 'missing');
        }

        $this->line('  <fg=gray>A verified backup is `./backup.sh` (it restores every dump into a scratch</>');
        $this->line('  <fg=gray>database and compares the row counts). The drill itself is `./restore.sh`,</>');
        $this->line('  <fg=gray>and it has to be done once on the real box by the person who will do it</>');
        $this->line('  <fg=gray>on the day — that is the whole of §7.</>');
    }

    private function box(): void
    {
        $heartbeat = \App\Models\AppSetting::row('scheduler_heartbeat')?->value;
        $age = $heartbeat ? (int) now()->diffInSeconds(\Illuminate\Support\Carbon::parse($heartbeat), true) : null;
        $this->ok('the scheduler is running', $age !== null && $age < 180,
            $age === null ? 'never ran' : "$age s ago");

        // A race left running from a test will confuse the first real one.
        $open = RaceSession::whereNull('finished_at')->whereNotNull('started_at')->count();
        $this->ok('no race is left running from a test', $open === 0, $open > 0 ? "$open still running" : 'none');

        $unlocks = GameLocationUnlock::whereNull('revoked_at')->count();
        $this->ok('no post is left open from a test', $unlocks === 0, $unlocks > 0 ? "$unlocks open" : 'none');

        $this->line('  <fg=gray>`eureka:doctor` covers the wiring; `/admin/kesiapan` covers the authoring</>');
        $this->line('  <fg=gray>chains and the venue network.</>');
    }

    private function section(string $title): void
    {
        $this->line("<options=bold>  $title</>");
    }

    private function ok(string $what, bool $passed, string $detail = ''): void
    {
        $passed ? $this->pass++ : $this->todo++;

        $this->line(sprintf('    %s %-46s %s',
            $passed ? '<fg=green>✓</>' : '<fg=yellow>·</>',
            $what,
            $detail ? "<fg=gray>$detail</>" : '',
        ));
    }
}
