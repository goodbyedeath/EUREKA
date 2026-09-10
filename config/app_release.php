<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Android client releases
    |--------------------------------------------------------------------------
    |
    | The APK is a shell: branding, features, questionnaires, outposts, indoor
    | plans and AR scenes all arrive from the server, so most changes need no
    | new build. What the server could not do until now is notice WHICH build is
    | talking to it — so a phone still running an old APK met a changed contract
    | and failed in the field with no explanation.
    |
    | `minimum_code` is the gate. It is 0 by default, which blocks nothing:
    | turning it on has to be a deliberate act before an event, never a surprise
    | during one. Raise it only when an older build genuinely cannot work.
    |
    */

    'latest_name' => env('APK_LATEST_NAME', '0.13'),
    'latest_code' => (int) env('APK_LATEST_CODE', 13),

    // Below this, the API refuses with 426 and the app must update. 0 = no gate.
    'minimum_code' => (int) env('APK_MINIMUM_CODE', 0),

    'download_url' => env('APK_DOWNLOAD_URL'),

    'notes' => env('APK_RELEASE_NOTES'),

];
