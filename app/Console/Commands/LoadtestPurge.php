<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove everything `loadtest:seed` made, and nothing else.
 *
 * It matches on the `LT_` prefix rather than on a timestamp or a count, so a purge cannot reach a real team
 * even if a harness run and an event ever share a database — which they should not, but the guard costs
 * nothing.
 */
class LoadtestPurge extends Command
{
    protected $signature = 'loadtest:purge {--force : skip the confirmation}';

    protected $description = 'Delete the LT_ teams, accounts, cards, tokens and attempts the harness created';

    public function handle(): int
    {
        if (env('LOADTEST') !== '1') {
            $this->error('Refusing: set LOADTEST=1 in the environment of the machine under test.');

            return self::FAILURE;
        }

        $prefix = LoadtestSeed::PREFIX;
        $userIds = User::where('name', 'like', $prefix.'%')->pluck('id');
        $teamIds = Team::where('name', 'like', $prefix.'%')->pluck('id');

        $this->line("Accounts: {$userIds->count()}   teams: {$teamIds->count()}");
        if ($userIds->isEmpty() && $teamIds->isEmpty()) {
            $this->info('Nothing to purge.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete all of it?', false)) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($userIds, $teamIds) {
            // Tokens and cards hang off the accounts and cascade; attempts and answers do too. Teams go
            // last, after their accounts have let go of them.
            DB::table('personal_access_tokens')
                ->where('tokenable_type', (new User)->getMorphClass())
                ->whereIn('tokenable_id', $userIds)->delete();
            User::whereIn('id', $userIds)->delete();
            Team::whereIn('id', $teamIds)->delete();
        });

        $this->info('Purged.');

        return self::SUCCESS;
    }
}
