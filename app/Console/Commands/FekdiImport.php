<?php

namespace App\Console\Commands;

use App\Services\FekdiIntegration;
use Illuminate\Console\Command;

/** Hourly: pull the client's member list, so people who sign up during the event can be found. */
class FekdiImport extends Command
{
    protected $signature = 'fekdi:import';

    protected $description = 'Import the FEKDI x IFSE member list into fekdi_participants';

    public function handle(FekdiIntegration $fekdi): int
    {
        if (! $fekdi->active()) {
            $this->line('skipped: integration switched off or not configured');

            return self::SUCCESS;
        }

        $result = $fekdi->import();
        $this->line(json_encode($result));

        return self::SUCCESS;
    }
}
