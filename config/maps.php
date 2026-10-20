<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Open Source Map Stack
    |--------------------------------------------------------------------------
    |
    | The station locator is built entirely on the open source OpenStreetMap
    | ecosystem, which needs no API key and bills nothing: raster tiles from
    | openstreetmap.org, address geocoding through the public Nominatim
    | instance, and driving directions from an OSRM server.
    |
    | Every value here is passed to the locator through data attributes on the
    | [data-locator] element, so this file stays the single source of truth for
    | map configuration.
    |
    */

    'tile_url' => env('MAP_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),

    'tile_attribution' => env(
        'MAP_TILE_ATTRIBUTION',
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    ),

    'max_zoom' => (int) env('MAP_MAX_ZOOM', 19),

    /*
    |--------------------------------------------------------------------------
    | Open Source Routing and Geocoding
    |--------------------------------------------------------------------------
    |
    | Nominatim is proxied server side because its usage policy requires an
    | identifying User-Agent, which a browser request cannot set. OSRM is
    | proxied the same way so the response shape stays stable.
    |
    */

    'nominatim_search_url' => env(
        'NOMINATIM_SEARCH_URL',
        'https://nominatim.openstreetmap.org/search'
    ),

    'nominatim_reverse_url' => env(
        'NOMINATIM_REVERSE_URL',
        'https://nominatim.openstreetmap.org/reverse'
    ),

    // Nominatim's usage policy requires an identifying User-Agent.
    'nominatim_user_agent' => env('NOMINATIM_USER_AGENT', 'OilandStationLocator/1.0'),

    'osrm_url' => env('OSRM_URL', 'https://router.project-osrm.org'),

    /*
    |--------------------------------------------------------------------------
    | Default View
    |--------------------------------------------------------------------------
    |
    | Fallback camera position for the locator, centred on Metro Manila and
    | framed to show the whole network. Views override this with the average
    | position of whatever stations they are actually showing, so an empty
    | locator lands somewhere sensible rather than at the origin.
    |
    */

    'default_latitude' => (float) env('MAP_DEFAULT_LATITUDE', 14.5995),

    'default_longitude' => (float) env('MAP_DEFAULT_LONGITUDE', 120.9842),

    'default_zoom' => (int) env('MAP_DEFAULT_ZOOM', 7),

];
