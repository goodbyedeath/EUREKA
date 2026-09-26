<?php

namespace App\Console\Commands;

use App\Services\PartnerConnector;
use Illuminate\Console\Command;

/** Hourly: pull the client's member list, so people who sign up during the event can be found. */
class PartnerImport extends Command
{
    protected $signature = 'partner:import';

    protected $description = 'Import the partner\'s member list into partner_participants';

    public function handle(PartnerConnector $partner): int
    {
        if (! $partner->active()) {
            $this->line('skipped: integration switched off or not configured');

            return self::SUCCESS;
        }

        $result = $partner->import();
        $this->line(json_encode($result));

        return self::SUCCESS;
    }
}
