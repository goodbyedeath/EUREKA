<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Walking directions between outposts. Free tier, 2000 requests/day.
    //
    // The key used to sit in QuestLocationController as an env() default, which meant a
    // live credential was committed and kept working whether or not .env defined it —
    // so nobody noticed it was never configured. Rotate at openrouteservice.org.
    'openroute' => [
        'key' => env('OPENROUTE_API_KEY'),
    ],

    /*
    | A partner's API: their member list in, each member's team total out (App\Services\PartnerConnector).
    | Every wire detail is configuration, so the next partner is a set of keys rather than a rewrite. Unset
    | means no partner is connected, which is the normal state between events.
    */
    'partner' => [
        'base_url' => env('PARTNER_BASE_URL'),
        'api_key' => env('PARTNER_API_KEY'),
        'api_key_header' => env('PARTNER_API_KEY_HEADER', 'X-API-KEY'),
        // Named in the note that goes with every point we send.
        'admin_name' => env('PARTNER_ADMIN_NAME', 'Questerra'),

        // Their member list. `rows` and `last_page` are dot paths into the response body; `fields` maps
        // our column to theirs. The defaults are FEKDI's shape, because it was the first and its shape is
        // as good a starting point as any.
        'members' => [
            'path' => env('PARTNER_MEMBERS_PATH', '/api/v1/members'),
            'page_param' => env('PARTNER_MEMBERS_PAGE_PARAM', 'page'),
            'limit_param' => env('PARTNER_MEMBERS_LIMIT_PARAM', 'limit'),
            'limit' => (int) env('PARTNER_MEMBERS_LIMIT', 100),
            'rows' => env('PARTNER_MEMBERS_ROWS', 'data'),
            'last_page' => env('PARTNER_MEMBERS_LAST_PAGE', 'meta.last_page'),
            'fields' => [
                'id' => env('PARTNER_FIELD_ID', 'id'),
                'name' => env('PARTNER_FIELD_NAME', 'name'),
                'email' => env('PARTNER_FIELD_EMAIL', 'email'),
                'avatar' => env('PARTNER_FIELD_AVATAR', 'avatar'),
                'joined_at' => env('PARTNER_FIELD_JOINED_AT', 'joined_at'),
            ],
        ],

        // Where a team's earned points go, and what that endpoint calls its arguments. `extra` is for
        // constants a partner requires — FEKDI needed `tipe: offline` to book them as venue points.
        'points' => [
            'path' => env('PARTNER_POINTS_PATH', '/api/v1/points'),
            'fields' => [
                'member' => env('PARTNER_POINTS_MEMBER_FIELD', 'member_google_id'),
                'amount' => env('PARTNER_POINTS_AMOUNT_FIELD', 'jumlah'),
                'note' => env('PARTNER_POINTS_NOTE_FIELD', 'note'),
                'admin' => env('PARTNER_POINTS_ADMIN_FIELD', 'admin_name'),
            ],
            'extra' => array_filter([
                'tipe' => env('PARTNER_POINTS_TYPE'),
            ]),
        ],
    ],

    /*
    | The separate GPS Tracker app. EUREKA only reads its public export, server-side, and keeps
    | its own copy of the routes (App\Services\TrackerMapSync).
    */
    'tracker' => [
        'base_url' => env('TRACKER_BASE_URL', 'https://tracker.questerra-series.com'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
