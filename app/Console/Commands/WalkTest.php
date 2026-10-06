<?php

namespace App\Console\Commands;

use App\Models\PostArrival;
use App\Models\TapNode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The LoRa walk test, read back from what actually arrived.
 *
 * SCALING-PLAN.md §7 asks for a walk test before the event, and §6 says to set transmit power "as
 * low as passes the walk test" — which needs a number, not an impression. Every arrival carries the
 * gateway's `rssi` and `snr`, so the walk is already recorded; this is the readout.
 *
 * Walk to each booth with a card, tap it a few times, then:
 *
 *   php artisan eureka:walk-test
 *   php artisan eureka:walk-test --minutes=30 --node=3
 */
class WalkTest extends Command
{
    protected $signature = 'eureka:walk-test
        {--minutes=120 : how far back to look}
        {--node= : only this node_id}';

    protected $description = 'Per-booth LoRa signal from the taps that arrived: margin, losses, battery';

    /**
     * SX1262 at 9.6 kbps air rate sits around −120 dBm sensitivity. The margin below is measured
     * against that, and the bands are the usual field rule of thumb rather than a specification:
     * under 10 dB a wall or a crowd will take the link out, over 20 dB it is comfortable.
     */
    private const SENSITIVITY_DBM = -120;

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $since = now()->subMinutes($minutes);

        $rows = PostArrival::query()
            ->where('received_at', '>=', $since)
            ->when($this->option('node'), fn ($q) => $q->where('node_id', (int) $this->option('node')))
            ->whereNotNull('rssi')
            ->selectRaw('node_id, COUNT(*) as taps, MIN(rssi) as worst, MAX(rssi) as best,
                         ROUND(AVG(rssi), 1) as mean, ROUND(AVG(snr), 1) as snr,
                         SUM(hmac_ok = 0) as bad, MAX(vbat_mv) as vbat,
                         COUNT(DISTINCT gateway) as gateways')
            ->groupBy('node_id')
            ->orderBy('node_id')
            ->get();

        $this->newLine();
        $this->line(sprintf('<options=bold>LoRa walk test</> — taps in the last %d minute(s)', $minutes));
        $this->newLine();

        if ($rows->isEmpty()) {
            $this->warn('  No taps with a signal reading in that window.');
            $this->line('  <fg=gray>Start a listener (`eureka:tap-listen /dev/ttyUSB0`), walk to a booth and tap a card.</>');
            $this->line('  <fg=gray>A tap recorded over HTTP carries no rssi, so it will not appear here.</>');
            $this->newLine();

            return self::SUCCESS;
        }

        $nodes = TapNode::whereIn('node_id', $rows->pluck('node_id'))->with('gameLocation:id,name')->get()->keyBy('node_id');

        $table = [];
        $weak = 0;

        foreach ($rows as $r) {
            $margin = (int) $r->worst - self::SENSITIVITY_DBM;
            $verdict = match (true) {
                $margin >= 20 => '<fg=green>comfortable</>',
                $margin >= 10 => '<fg=yellow>thin — move the node or raise power</>',
                default => '<fg=red>will drop — reposition before the event</>',
            };

            if ($margin < 20) {
                $weak++;
            }

            $table[] = [
                $r->node_id,
                mb_substr($nodes[$r->node_id]?->gameLocation?->name ?? $nodes[$r->node_id]?->label ?? '—', 0, 22),
                $r->taps,
                $r->worst.' / '.$r->mean.' / '.$r->best,
                $margin.' dB',
                $r->snr ?? '—',
                $r->gateways,
                $r->vbat ? round($r->vbat / 1000, 2).' V' : '—',
                $r->bad > 0 ? '<fg=red>'.$r->bad.'</>' : '0',
                $verdict,
            ];
        }

        $this->table(
            ['node', 'post', 'taps', 'rssi worst/avg/best', 'margin', 'snr', 'gw', 'batt', 'bad sig', ''],
            $table,
        );

        // Which booths said nothing at all. A silent node is the finding a walk test exists to produce,
        // and it cannot appear in a table built from arrivals.
        $heard = $rows->pluck('node_id');
        $silent = TapNode::where('is_active', true)->whereNotIn('node_id', $heard)->orderBy('node_id')->get();

        if ($silent->isNotEmpty()) {
            $this->newLine();
            $this->line('<fg=red>Nothing heard from '.$silent->count().' active node(s):</> '
                .$silent->map(fn (TapNode $n) => $n->node_id.' ('.($n->gameLocation?->name ?? 'no post').')')->implode(', '));
            $this->line('  <fg=gray>Either it was not walked to, or its link does not reach. Both are worth knowing now.</>');
        }

        $this->newLine();
        $this->line(sprintf('  %d node(s) heard, %d with less than 20 dB of margin, %d silent.',
            $rows->count(), $weak, $silent->count()));

        if ($rows->where('gateways', '<', 2)->isNotEmpty()) {
            $this->line('  <fg=yellow>Some nodes were heard by only one gateway.</> Two is the design — one gateway');
            $this->line('  <fg=yellow>is one device that can silence every booth. Check the second one is listening.</>');
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
