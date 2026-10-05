<?php

return [

    /*
    |--------------------------------------------------------------------------
    | How many people are playing
    |--------------------------------------------------------------------------
    |
    | The one number the limiters are sized from. SCALING-PLAN.md §1 and §3e: 5,000 players arriving
    | over roughly ten minutes is ~500 logins a minute, and before this was configurable the ceiling
    | was 60 — so our own code would have turned the venue away in the first minute.
    |
    | Raise it for a bigger event, leave it for a small one. It is not a guess about load: it is a
    | statement of how many people are expected, and the ceilings are derived from it below.
    |
    */

    'players' => (int) env('VENUE_PLAYERS', 5000),

    /*
    | How long the doors are open, in minutes — the window those players arrive in.
    */

    'doors_minutes' => (int) env('VENUE_DOORS_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Login ceilings
    |--------------------------------------------------------------------------
    |
    | `per_minute_per_address` is the blunt ceiling, and what it is for is bulk scraping — not
    | password guessing, which is handled per account. It has to be generous because of a fact about
    | venues: whether this server sees 5,000 addresses or one is a property of the network, not of
    | the players. With per-phone addresses on the LAN it is never approached; behind one NAT, or
    | behind a proxy whose address we read instead of the client's, every phone shares the bucket.
    |
    | So the default is derived with headroom: arrivals per minute × 3, floor of 120. At 5,000 over
    | ten minutes that is 1,500/min — a number a room passes and a script run against the open
    | internet does not.
    |
    | `failures_per_minute_per_address` is the one that catches a spray, because it counts only
    | *wrong* credentials. A venue produces almost none: a mistyped password or a creased card, a
    | handful a minute at most. Someone working through a stolen list produces nothing else.
    |
    */

    'logins' => [
        'per_minute_per_address' => (int) env('VENUE_LOGINS_PER_MINUTE', 0) ?: max(
            120,
            (int) ceil((int) env('VENUE_PLAYERS', 5000) / max(1, (int) env('VENUE_DOORS_MINUTES', 10))) * 3,
        ),

        'failures_per_minute_per_address' => (int) env('VENUE_LOGIN_FAILURES_PER_MINUTE', 60),

        /*
        | Attempts per account per minute. This is the password-guessing limit and it stays strict:
        | it is keyed on the account, so 5,000 people logging in are 5,000 separate buckets and the
        | size of the event does not touch it.
        */
        'per_minute_per_account' => (int) env('VENUE_LOGINS_PER_ACCOUNT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxies whose forwarded address we believe
    |--------------------------------------------------------------------------
    |
    | Empty means: trust nobody, use the address the connection came from.
    |
    | **Only ever list the proxy itself.** Trusting a range means anything inside it can set
    | `X-Forwarded-For` and be whoever it likes — so a player subnet in this list would hand every
    | phone the ability to forge its address and walk through every per-address limit. On the venue
    | LAN nginx talks to php-fpm directly and no forwarded header exists, which is why the default
    | here is empty and correct.
    |
    | `cloudflare` is a keyword, expanded to Cloudflare's published ranges — the shared host is
    | behind it today, and without this every /api/v1 request was rate-limited by a Cloudflare edge
    | address rather than by the phone's (App\Http\Middleware\TrustProxies).
    |
    | On the workstation, if `docker compose` is running with the userland proxy enabled, nginx sees
    | the bridge gateway for every request instead of the real client. The fix is the daemon setting
    | (`"userland-proxy": false`), not a trusted proxy — a forwarded header is not involved, so there
    | is nothing here that can repair it. `/admin/kesiapan` counts the distinct addresses logins
    | arrive from, which is how you find out.
    |
    */

    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('VENUE_TRUSTED_PROXIES', 'cloudflare')),
    ))),

];
