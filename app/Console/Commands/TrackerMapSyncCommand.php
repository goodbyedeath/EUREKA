<?php

namespace App\Console\Commands;

use App\Services\TrackerMapSync;
use Illuminate\Console\Command;

class TrackerMapSyncCommand extends Command
{
    protected $signature = 'tracker:sync-map {--limit=25 : How many recorded sessions to read}';

    protected $description = 'Copy routes and pins from the GPS Tracker app into EUREKA';

    public function handle(TrackerMapSync $sync): int
    {
        $result = $sync->sync((int) $this->option('limit'));

        if ($result['error']) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        $this->info("Routes: {$result['routes']}, markers: {$result['markers']}.");

        return self::SUCCESS;
    }
}
