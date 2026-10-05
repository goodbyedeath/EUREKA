<?php

namespace App\Console\Commands;

use App\Models\LoginCard;
use App\Models\PartnerParticipant;
use App\Models\Team;
use App\Models\User;
use App\Services\LoginCardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Teams, accounts, login cards and API tokens for the load harness, written out as accounts.json.
 *
 * Deliberately awkward to run. It creates thousands of rows and must never be fired at the live event
 * server, so it refuses unless `LOADTEST=1` is in the environment — a line nobody adds to a production
 * `.env` by accident — and everything it creates is named `LT_…` so `loadtest:purge` can be exact rather
 * than clever.
 *
 *   LOADTEST=1 php artisan loadtest:seed --teams=500 --accounts=5000 --out=tools/loadtest/k6/accounts.json
 */
class LoadtestSeed extends Command
{
    protected $signature = 'loadtest:seed
        {--teams=50 : how many teams}
        {--accounts=100 : how many player accounts, spread across those teams}
        {--out=tools/loadtest/k6/accounts.json : where to write the account file k6 reads}';

    protected $description = 'Create LT_ teams, accounts, login cards and tokens for the k6 harness';

    public const PREFIX = 'LT_';

    public function handle(LoginCardService $cards): int
    {
        if (env('LOADTEST') !== '1') {
            $this->error('Refusing: set LOADTEST=1 in the environment of the machine under test.');
            $this->line('This creates thousands of rows. It is not meant to touch the live event server.');

            return self::FAILURE;
        }

        $teams = max(1, (int) $this->option('teams'));
        $accounts = max(1, (int) $this->option('accounts'));
        $out = (string) $this->option('out');

        $this->warn("About to create {$teams} teams and {$accounts} accounts prefixed ".self::PREFIX);
        $this->line('Database: '.config('database.connections.'.config('database.default').'.database'));
        if (! $this->option('no-interaction') && ! $this->confirm('Continue?', false)) {
            return self::FAILURE;
        }

        // A post and a questionnaire to aim the post-flow scenario at; both must already exist.
        $post = DB::table('game_locations')->where('is_active', true)->first(['id']);
        $quest = DB::table('questionnaires')->where('is_active', true)->first(['id', 'qr_code']);
        if (! $post || ! $quest) {
            $this->error('Needs at least one active game location and one active questionnaire to aim at.');

            return self::FAILURE;
        }

        $rows = [];
        $password = 'loadtest-'.Str::random(8);
        $bar = $this->output->createProgressBar($accounts);

        for ($i = 1; $i <= $accounts; $i++) {
            $teamNo = (int) ceil($i / max(1, (int) ceil($accounts / $teams)));
            $team = Team::firstOrCreate(
                ['name' => self::PREFIX.'Tim '.str_pad((string) $teamNo, 4, '0', STR_PAD_LEFT)],
                ['initial_points' => 0],
            );

            $user = User::create([
                'name' => self::PREFIX.'HP '.$i,
                'email' => strtolower(self::PREFIX).'hp'.$i.'@loadtest.invalid',
                'password' => bcrypt($password),
                'role' => 'user',
                'team_id' => $team->id,
            ]);

            // A printed card, because that is how a team really logs in at the door.
            $code = 'LT-'.Str::upper(Str::random(10));
            LoginCard::create([
                'user_id' => $user->id,
                'code_hash' => $cards->hashFor($code),
                'code_encrypted' => encrypt($code),
                'password_encrypted' => encrypt($password),
                'batch' => self::PREFIX.'harness',
                'created_by' => null,
            ]);

            $rows[] = [
                'user_id' => $user->id,
                'team' => $team->name,
                'email' => $user->email,
                'password' => $password,
                'login_code' => $code,
                // Pre-minted so the steady-state scenarios do not have to log in first.
                'token' => $user->createToken('k6')->plainTextToken,
                'post_id' => $post->id,
                'qr_code' => $quest->qr_code,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $path = base_path($out);
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Wrote '.count($rows).' accounts to '.$path);
        $this->line('Shared password for every account: '.$password);
        $this->newLine();
        $this->line('Then, from tools/loadtest/k6:');
        $this->line('  TARGET=http://<workstation> PHONES='.$accounts.' k6 run all.js');
        $this->newLine();
        $this->warn('Remember: LOADTEST=1 php artisan loadtest:purge  when the run is finished.');

        return self::SUCCESS;
    }
}
