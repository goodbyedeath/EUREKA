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

        // Client-agent reports. Generous enough for a build agent working through a task
        // list, tight enough that an open write endpoint cannot be used to fill a table.
        RateLimiter::for('feedback', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Login attempts, keyed on the account being tried **and** the address.
        //
        // Keyed on the address alone this was five attempts a minute for a whole venue:
        // every team is on the same WiFi, so at the moment an event starts and twenty
        // teams log in together, five get through and fifteen are turned away — from our
        // own code, not the host's.
        //
        // The pair keeps what the limit is actually for. Guessing one account's password
        // is still capped at five tries a minute however many addresses are used, and one
        // address still cannot grind through accounts; what it no longer does is make
        // twenty different teams queue behind each other.
        RateLimiter::for('auth', function (Request $request) {
            $account = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('acct:' . $account . '|' . $request->ip()),
                // A generous ceiling per address, so a single connection cannot be used
                // to spray attempts across many accounts. Sized for a room of teams
                // logging in at once, not for one person typing.
                Limit::perMinute(60)->by('ip:' . $request->ip()),
            ];
        });
    }
}
