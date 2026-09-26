<?php

namespace App\Console\Commands;

use App\Services\PartnerConnector;
use Illuminate\Console\Command;

/** Every minute: recompute registered participants' points, then send increases to the client. */
class PartnerPush extends Command
{
    protected $signature = 'partner:push';

    protected $description = 'Mirror each partner participant\'s points from their team score, then send the increases';

    public function handle(PartnerConnector $partner): int
    {
        // Recording is local and cheap, so it runs even while the switch is off; only sending stops.
        $changed = $partner->refreshPoints();
        $result = $partner->sendIncreases();

        $this->line("refreshed={$changed} ".json_encode($result));

        return self::SUCCESS;
    }
}
