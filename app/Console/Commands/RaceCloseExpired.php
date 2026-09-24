<?php

namespace App\Console\Commands;

use App\Models\RaceSession;
use Illuminate\Console\Command;

/**
 * Close the runs whose time has run out.
 *
 * `/race/status` and the station gate both close an overdue run the moment they are asked, so a team
 * that is still using its phone needs nothing from here. This exists for the ones that are not: a
 * phone put in a pocket, a battery that died, a team that walked off. Without it their clock would sit
 * unfinished in the admin screens and the leaderboard until somebody opened the app again.
 *
 * The finish is stamped at the deadline, never at the moment this happened to run, so a cron that is
 * a minute late does not lengthen anyone's race.
 */
class RaceCloseExpired extends Command
{
    protected $signature = 'race:close-expired';

    protected $description = 'Finish race sessions whose START code time limit has passed';

    public function handle(): int
    {
        $closed = 0;

        RaceSession::with('raceStart')
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->whereHas('raceStart', fn ($q) => $q->whereNotNull('time_limit_minutes'))
            ->chunkById(200, function ($sessions) use (&$closed) {
                foreach ($sessions as $session) {
                    if ($session->finishIfOverTime()) {
                        $closed++;
                    }
                }
            });

        if ($closed > 0) {
            $this->info("{$closed} race session(s) closed on their time limit.");
        }

        return self::SUCCESS;
    }
}
