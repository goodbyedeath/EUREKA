<?php

namespace App\Console\Commands;

use App\Services\FekdiIntegration;
use Illuminate\Console\Command;

/** Every minute: recompute registered participants' points, then send increases to the client. */
class FekdiSync extends Command
{
    protected $signature = 'fekdi:sync';

    protected $description = 'Refresh FEKDI participants\' points from their team score and send increases to the client';

    public function handle(FekdiIntegration $fekdi): int
    {
        // Recording is local and cheap, so it runs even while the switch is off; only sending stops.
        $changed = $fekdi->refreshPoints();
        $result = $fekdi->sendIncreases();

        $this->line("refreshed={$changed} ".json_encode($result));

        return self::SUCCESS;
    }
}
