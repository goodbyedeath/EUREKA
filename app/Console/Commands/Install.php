<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FacilitatorPin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Bring up a clean EUREKA: schema, defaults, one admin, the facilitator PIN.
 *
 * For the workstation, where the operator wants to start empty rather than carry Hostinger's data
 * across (5 Oct). A clean start is genuinely simpler — no dump, no 370 MB of storage, and `APP_KEY`
 * can be generated rather than copied, because there is nothing encrypted to decrypt — but it leaves
 * two gaps that are only noticed at the worst moment:
 *
 *   - **No admin account.** `UserSeeder` exists and makes `admin@gmail.com` with the password `123`.
 *     That is fine on a laptop and indefensible on a box that 5,000 phones can reach, so this command
 *     asks for a real one and never writes that seeder's.
 *   - **No facilitator PIN.** `FacilitatorPin` fails closed, so facilitator scoring is impossible
 *     until an admin sets it — and nobody finds out until the first team hands in a game at the first
 *     post.
 *
 * It refuses to touch a database that already holds an event. It never deletes anything.
 *
 *   php artisan eureka:install
 */
class Install extends Command
{
    protected $signature = 'eureka:install
        {--admin-name= : the admin\'s display name}
        {--admin-email= : the admin\'s login email}
        {--admin-password= : skip the prompt (visible in shell history — prefer the prompt)}
        {--pin= : the facilitator PIN for this event}
        {--force : proceed even though the database already holds data}';

    protected $description = 'Set up a clean installation: defaults, an admin account, the facilitator PIN';

    /** Tables whose contents mean "this is somebody's event, do not touch it". */
    private const EVENT_TABLES = [
        'teams', 'questionnaires', 'questions', 'game_locations', 'indoor_maps',
        'quiz_attempts', 'login_cards', 'game_archives', 'quest_locations',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('<options=bold>EUREKA — clean installation</>');
        $this->newLine();

        if (! Schema::hasTable('users')) {
            $this->error('No schema yet. Run `./deploy.sh migrate` (or `php artisan migrate --force`) first.');

            return self::FAILURE;
        }

        if (! $this->databaseIsEmpty()) {
            return self::FAILURE;
        }

        // Defaults that live in the database rather than in config: the feature rows and the hero
        // slides. Both seeders are written to cope with rows already being there.
        $this->line('  Seeding defaults…');
        $this->callSilently('db:seed', ['--force' => true]);
        $this->line(sprintf('  <fg=green>features</>  %d rows, %d enabled',
            DB::table('feature_settings')->count(),
            DB::table('feature_settings')->where('is_enabled', true)->count(),
        ));

        if (($admin = $this->createAdmin()) === null) {
            return self::FAILURE;
        }

        $this->setFacilitatorPin($admin);

        $this->newLine();
        $this->line('  Warming the caches…');
        $this->callSilently('eureka:warm');

        $this->whatIsStillEmpty();

        return self::SUCCESS;
    }

    /**
     * Refuse to run against an event. This command creates an admin and a PIN; doing that on top of
     * live data is how two people end up with different ideas of who the admin is.
     */
    private function databaseIsEmpty(): bool
    {
        $found = [];

        foreach (self::EVENT_TABLES as $table) {
            if (Schema::hasTable($table) && ($n = DB::table($table)->count()) > 0) {
                $found[] = "$table: $n";
            }
        }

        if (($players = User::where('role', '!=', 'admin')->count()) > 0) {
            $found[] = "player accounts: $players";
        }

        if (! $found) {
            return true;
        }

        if ($this->option('force')) {
            $this->warn('  Database is not empty ('.implode(', ', $found).') — continuing because --force was given.');
            $this->warn('  Nothing will be deleted; an admin account will be added to what is already there.');

            return true;
        }

        $this->error('This database already holds an event:');
        foreach ($found as $line) {
            $this->line('    '.$line);
        }
        $this->newLine();
        $this->line('  Nothing has been changed. If you meant to start clean, bring up an empty database');
        $this->line('  (`./deploy.sh nuke` deletes the volumes — there is no undo — then `./deploy.sh up`).');
        $this->line('  If you meant to add an admin to this existing event, run again with --force.');
        $this->newLine();

        return false;
    }

    private function createAdmin(): ?User
    {
        $this->newLine();
        $this->line('<options=bold>  The admin account</>');

        $name = $this->option('admin-name') ?: $this->ask('  Name', 'Event Admin');
        $email = strtolower(trim((string) ($this->option('admin-email') ?: $this->ask('  Email'))));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('  That is not an email address.');

            return null;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("  $email already exists.");

            return null;
        }

        $password = $this->option('admin-password');

        if (! $password) {
            // Twelve, not eight: this box answers to a whole venue, and the admin can start and stop
            // every team's race.
            $password = $this->secret('  Password (12 characters or more)');

            if (strlen((string) $password) < 12) {
                $this->error('  Too short. Nothing has been created.');

                return null;
            }

            if ($password !== $this->secret('  Again')) {
                $this->error('  They do not match. Nothing has been created.');

                return null;
            }
        } elseif (strlen($password) < 12) {
            $this->error('  --admin-password is shorter than 12 characters.');

            return null;
        }

        // No email_verified_at: User does not implement MustVerifyEmail, no route carries the
        // `verified` middleware, and this app sends no email at all — two of the three live admins have
        // it null and sign in fine. Setting it would be a column nothing reads.
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->line("  <fg=green>admin</>     #{$admin->id} $email");

        return $admin;
    }

    /**
     * The PIN a facilitator types on the team's own phone to release a game's points. It fails
     * closed, so skipping this leaves facilitator scoring dead until somebody sets it in the admin.
     */
    private function setFacilitatorPin(User $admin): void
    {
        $this->newLine();
        $this->line('<options=bold>  The facilitator PIN</>');
        $this->line('  <fg=gray>Typed by the crew on the team\'s phone to release a game\'s points. Scoring stays</>');
        $this->line('  <fg=gray>blocked until it is set — the API answers facilitator_pin_not_set.</>');

        $pin = $this->option('pin');

        if (! $pin && $this->input->isInteractive()) {
            $pin = $this->secret('  PIN (leave blank to set it later in the admin)');
        }

        if (! $pin) {
            $this->warn('  Not set. Set it before the event, or no facilitator can score a game.');

            return;
        }

        app(FacilitatorPin::class)->set((string) $pin, $admin->id);
        $this->line('  <fg=green>pin</>       set');
    }

    /** What a clean database has none of, in the order an event is actually built. */
    private function whatIsStillEmpty(): void
    {
        $this->newLine();
        $this->line('<options=bold>  What to author next, in this order</>');
        $this->newLine();

        $steps = [
            ['/admin/quest-locations', 'Outdoor posts, if this event has any (coordinates + radius)'],
            ['/admin/games', 'AR Outposts: the 3D scene per post, and access_mode (manual indoors)'],
            ['/admin/questionnaires', 'The questions. Each gets its QR code on creation — that is the printed one'],
            ['/admin/indoor-maps', 'The floor plan per team, each spot linked to its AR Outpost'],
            ['/admin/race-start', 'The START code and its time limit'],
            ['/admin/team-management', 'Teams, then Login Cards to print the accounts'],
            ['/admin/panduan', 'The in-app guide, and the branding/logo under dashboard management'],
            ['/admin/outpost-access', 'Nothing to set up — this is the desk the crew runs the event from'],
        ];

        foreach ($steps as [$path, $what]) {
            $this->line(sprintf('  <fg=cyan>%-26s</> %s', $path, $what));
        }

        $this->newLine();
        $this->line('  <fg=gray>Check the wiring with `php artisan eureka:doctor`, and re-run `eureka:warm`</>');
        $this->line('  <fg=gray>after every `optimize:clear`.</>');
        $this->newLine();
    }
}
