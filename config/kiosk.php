<?php

return [

    /*
    |--------------------------------------------------------------------------
    | How long a computed leaderboard may be reused
    |--------------------------------------------------------------------------
    |
    | Seconds. Every screen in the venue polls the same board, so without this each one pays for its
    | own computation (SCALING-PLAN.md §3b). Ten seconds is the figure in the plan, and it is not
    | the staleness the room sees: the clock is re-read from the cached instants on every request,
    | and any write that moves a score bumps the cache outright, so a correction made by hand shows
    | up on the next poll rather than at the end of a TTL.
    |
    | 0 disables the cache and computes per request — the old behaviour, kept for debugging a number
    | somebody disputes.
    |
    */

    'leaderboard_ttl' => (int) env('KIOSK_LEADERBOARD_TTL', 10),

];
