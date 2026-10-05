<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire registers POST /livewire/update itself, so its middleware cannot be
        // set in routes/web.php — this is the only place it can be given a throttle.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', 'throttle:livewire']);
        });

        // API endpoints, keyed by the authenticated user and only falling back to the IP.
        //
        // Keying on IP alone is wrong for this app: at a venue every team is on the same
        // WiFi, so twenty phones share one address and a per-IP limit of 60 divides into
        // three requests per team per minute. Once a team has a token there is a better
        // key than the address they happen to be behind.
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by('u' . $request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        // Public kiosk endpoints. These are polled by design: the LED board alone asks
        // for data every 5s and GPS every 10s, which is 18 requests a minute before
        // anyone opens the leaderboard or the map from the same venue connection — and
        // 30/min left almost no headroom, so the board started answering 429 to itself.
        //
        // The limit is here to stop bulk scraping, not to ration a projector. Sized so
        // every kiosk screen plus an admin laptop can share one IP comfortably.
        RateLimiter::for('kiosk', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // The Livewire endpoint. Every admin click lands here, and it re-renders a whole
        // component per request, so it is both the busiest and the most expensive route the
        // app serves — and until now the only one with no ceiling at all.
        //
        // The point is not to protect the server. It is to fail *predictably*: without this,
        // the first thing to say no is the host's edge, which answers with an empty-bodied
        // 429 that kills the page and tells the admin nothing. Laravel's own 429 carries
        // Retry-After and the Livewire client backs off on it. Sized for a person clicking,
        // not for a poll: 200/min is roughly three actions a second, sustained.
        RateLimiter::for('livewire', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(200)->by('lw:' . $request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        // Writing a GPS position. Fed by navigator.geolocation.watchPosition, which
        // fires as fast as the sensor produces a fix — roughly once a second on a moving
        // phone — and had no ceiling on either side. One write every two seconds is far
        // more than a live map needs, and it is keyed per user so a venue's teams do not
        // share a budget.
        RateLimiter::for('tracking', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(30)->by('pos:' . $request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        // Booth readers. Fifteen nodes, a tap each few minutes, plus a heartbeat every five:
        // the whole venue averages about one message a second (SCALING-PLAN §6), so 120/min per
        // gateway address is roughly double the busiest minute an event can produce. Keyed on the
        // address because a gateway is a fixed box at the rack, not a phone in a crowd.
        RateLimiter::for('device', fn (Request $request) => Limit::perMinute(120)->by('dev:'.$request->ip()));

        // Client-agent reports. Generous enough for a build agent working through a task
        // list, tight enough that an open write endpoint cannot be used to fill a table.
        RateLimiter::for('feedback', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Login cards. The code carries 192 bits, so guessing is not the risk — a room of teams on
        // one venue WiFi scanning at the briefing is (SCALING-PLAN.md §3e).
        //
        // Sized from config/venue.php rather than from a number typed here: at 5,000 players over ten
        // minutes the venue needs ~500 logins a minute and this used to allow 60, so our own code
        // would have turned the event away in its first minute. The ceiling now derives from how many
        // people are expected, with headroom.
        //
        // A ceiling per address is all this can be — a card login names no account until the code is
        // matched — so the spray protection is in the controller, where a *wrong* code can be counted
        // separately from a right one.
        RateLimiter::for('login-code', fn (Request $request) => Limit::perMinute(
            (int) config('venue.logins.per_minute_per_address', 1500)
        )->by('ip:'.$request->ip()));

        // Password login, and the two limits do different jobs.
        //
        // Guessing one account's password is capped per *account*, not per address. That is the limit
        // that matters and it is deliberately strict: keyed on the account, 5,000 people logging in
        // are 5,000 separate buckets, so the size of the event never relaxes it. Keyed on the address
        // as well — as it was — it would have meant that at a venue where every phone shares one
        // address, five teams got in and the rest were turned away by us.
        //
        // The per-address ceiling stays, for bulk scraping, and comes from the same venue figure.
        RateLimiter::for('auth', function (Request $request) {
            $account = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('venue.logins.per_minute_per_account', 5))->by('acct:'.$account),
                Limit::perMinute((int) config('venue.logins.per_minute_per_address', 1500))->by('ip:'.$request->ip()),
            ];
        });

        // The kiosk board is computed once for the whole venue and cached (SCALING-PLAN.md §3b), so
        // something has to say when it is out of date. Every model here can move a number on the big
        // screen: an answer graded, a facilitator's assessment, a tapped 3D object, a correction made
        // by hand, a clock started or stopped, an attempt completed, a team's starting balance
        // changed. A TTL alone would be wrong — the crew fixes a score and then stands in front of
        // the screen waiting for it.
        //
        // `User` is deliberately absent. Moving an account between teams would change the board, but
        // a user row is also saved on every login, and bumping there would empty the cache 5,000
        // times during exactly the minute it is most needed. A membership change is picked up when
        // the TTL expires.
        foreach ([
            \App\Models\UserAnswer::class,
            \App\Models\GameAssessment::class,
            \App\Models\HotspotClaim::class,
            \App\Models\TeamScoreAdjustment::class,
            \App\Models\RaceSession::class,
            \App\Models\QuizAttempt::class,
            \App\Models\GameLocationUnlock::class,
            \App\Models\Team::class,
        ] as $model) {
            $model::saved(fn () => \App\Services\Leaderboard::bump());
            $model::deleted(fn () => \App\Services\Leaderboard::bump());
        }
    }
}
