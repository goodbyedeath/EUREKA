<?php

return [

    /*
    |--------------------------------------------------------------------------
    | How long the content version may be reused
    |--------------------------------------------------------------------------
    |
    | Seconds. `OfflineController::contentUpdatedAt()` is eleven MAX() lookups and it is read on
    | every /race/status poll — the most frequent call in an event — so the answer is held briefly
    | rather than recomputed per request (SCALING-PLAN.md §3c).
    |
    | It is also the manifest's cache key, so this is the longest a crew edit can take to reach a
    | phone that is already asking. Two seconds is invisible to the crew and removes ten queries
    | from the hottest endpoint in the venue.
    |
    | 0 computes it every time.
    |
    */

    'version_ttl' => (int) env('OFFLINE_VERSION_TTL', 2),

];
