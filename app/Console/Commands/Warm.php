<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\OfflineController;
use App\Models\Questionnaire;
use App\Services\Leaderboard;
use App\Services\OfflineManifest;
use App\Services\OfflineQuestionnaireSeal;
use Illuminate\Console\Command;

/**
 * Pay the expensive first request yourself, at deploy time, instead of leaving it to a player.
 *
 * `artisan optimize:clear` runs `cache:clear`, and three things in this app are expensive exactly
 * once after that:
 *
 *   - the offline seal's PBKDF2 master keys — 100,000 iterations per questionnaire QR code, 114 ms
 *     each on this host, 1.7 s for fifteen posts;
 *   - the shared offline manifest built on top of them;
 *   - the kiosk leaderboard.
 *
 * In an event that costs the first team to press Sync, in front of the crew, at the worst moment.
 * Run this straight after `optimize` — `docker/entrypoint.sh` already does.
 *
 *   php artisan eureka:warm
 */
class Warm extends Command
{
    protected $signature = 'eureka:warm';

    protected $description = 'Build the caches a deploy just emptied: seal keys, offline manifest, leaderboard';

    public function handle(): int
    {
        $this->newLine();

        // The seal keys first: the manifest is built out of them, so warming it in the other order
        // would hide where the time actually goes.
        $this->time('offline seal keys', function () {
            $seal = app(OfflineQuestionnaireSeal::class);
            $n = 0;

            foreach (Questionnaire::where('is_active', true)->get()->load('extraPosts:id') as $q) {
                $seal->manifestEntry($q);
                $n++;
            }

            return $n.' questionnaire'.($n === 1 ? '' : 's');
        });

        $this->time('offline manifest (shared part)', function () {
            $shared = app(OfflineManifest::class)->shared();

            return sprintf('%d posts, %d models, version %s',
                $shared['counts']['game_locations'] ?? 0,
                $shared['counts']['models'] ?? 0,
                OfflineController::contentUpdatedAt() ?? 'empty',
            );
        });

        $this->time('kiosk leaderboard', function () {
            $board = app(Leaderboard::class)->board();

            return count($board['teams']).' teams';
        });

        $this->newLine();
        $this->line('<fg=gray>The next sync and the next kiosk poll are now cache reads.</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function time(string $label, callable $work): void
    {
        $started = microtime(true);

        try {
            $detail = (string) $work();
        } catch (\Throwable $e) {
            $this->line(sprintf('  <fg=red>%-32s failed</> — %s', $label, $e->getMessage()));

            return;
        }

        $this->line(sprintf('  <fg=green>%-32s</> %6.0f ms   <fg=gray>%s</>', $label, (microtime(true) - $started) * 1000, $detail));
    }
}
