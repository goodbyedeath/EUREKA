<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basemap tiles
    |--------------------------------------------------------------------------
    |
    | Every map in the app (admin, team, kiosk) reads these values through
    | window.EUREKA_MAP, injected by resources/views/partials/map-config.blade.php.
    | Change the provider here — or in .env — and every map follows.
    |
    | The default is OpenStreetMap's public tile server, which is fine for
    | development but NOT for an event: their usage policy forbids heavy use, and
    | the kiosk polls continuously for hours. Before running a real event, point
    | MAP_TILE_URLS at a provider you have an account with, e.g.
    |
    |   MAP_TILE_URLS="https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=YOUR_KEY"
    |   MAP_TILE_ATTRIBUTION="© MapTiler © OpenStreetMap contributors"
    |
    | Multiple subdomains may be comma-separated; MapLibre will round-robin them.
    |
    */

    'tiles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MAP_TILE_URLS', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'))
    ))),

    'attribution' => env('MAP_TILE_ATTRIBUTION', '&copy; OpenStreetMap contributors'),

    'maxZoom' => (int) env('MAP_TILE_MAX_ZOOM', 19),

    /*
    |--------------------------------------------------------------------------
    | Satellite overlay (optional)
    |--------------------------------------------------------------------------
    |
    | Open ground often reads better from imagery than from a street map. Set
    | MAP_SATELLITE_URLS to enable it; leave it empty and the option stays off.
    |
    */

    'satellite' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MAP_SATELLITE_URLS', ''))
    ))),

    'satelliteAttribution' => env('MAP_SATELLITE_ATTRIBUTION', ''),

];
