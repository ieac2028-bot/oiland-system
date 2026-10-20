<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the public OSRM demo server (router.project-osrm.org).
 *
 * OSRM is entirely open source, so the locator stays free of proprietary map
 * APIs. The demo server is rate limited, so failures degrade gracefully into an
 * error payload the map can render as a straight-line hint.
 */
class OsrmRouter
{
    /**
     * @return array<string, mixed>
     */
    public function route(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
        string $profile = 'driving',
    ): array {
        $base = rtrim(config('maps.osrm_url'), '/');
        $coordinates = sprintf(
            '%F,%F;%F,%F',
            $originLng,
            $originLat,
            $destinationLng,
            $destinationLat,
        );

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->get("{$base}/route/v1/{$profile}/{$coordinates}", [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                    'steps' => 'false',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('OSRM request failed.', ['message' => $exception->getMessage()]);

            return $this->failed('The routing service is unreachable right now.');
        }

        if ($response->failed()) {
            return $this->failed('The routing service returned an error.');
        }

        $routes = $response->json('routes') ?? [];

        if ($routes === []) {
            return $this->failed('No driving route could be found between these points.');
        }

        $route = $routes[0];

        return [
            'ok' => true,
            'provider' => 'OSRM',
            'distance' => $route['distance'] ?? null,
            'duration' => $route['duration'] ?? null,
            'geometry' => $route['geometry'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function failed(string $message): array
    {
        return [
            'ok' => false,
            'provider' => 'OSRM',
            'message' => $message,
        ];
    }
}
