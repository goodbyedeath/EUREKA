<?php

return [

    /*
    | A questionnaire can be scanned only after the team scanned START and checked in at the post it is
    | linked to (App\Services\StationGate). Leave on. Switching it off is an .env change on the server —
    | never something a request can do — meant for the scratch test scripts and a last-resort escape.
    */
    'station_gate' => env('STATION_GATE', true),

];
