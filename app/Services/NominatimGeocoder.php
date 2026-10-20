<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding and reverse geocoding through the public Nominatim instance.
 *
 * Nominatim' usage policy requires an identifying User-Agent and forbids heavy
 * use, so results are cached and requests are throttled.
 */
class NominatimGeocoder
{
    /**
     * Geocode a free-form address into a latitude/longitude pair.
     *
     * @return array{latitude: float|null, longitude: float|null, display_name: string|null, found: bool}
     */
    public function geocode(string $address, ?int $branchId = null): array
    {
        $cacheKey = 'geocode:'.sha1(mb_strtolower(trim($address)).($branchId ?? 'global'));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(10)
                ->get(config('maps.nominatim_search_url'), [
                    'q' => str_contains(mb_strtolower($address), 'philippines') ? $address : $address.', Philippines',
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Nominatim geocoding failed.', ['message' => $exception->getMessage()]);

            return $this->empty();
        }

        if (! $response->successful()) {
            Log::warning('Nominatim geocoding returned an unsuccessful response.', [
                'status' => $response->status(),
            ]);

            return $this->empty();
        }

        $result = $response->json()[0] ?? null;

        $payload = $result === null ? $this->empty() : [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'display_name' => $result['display_name'] ?? null,
            'found' => true,
        ];

        if ($payload['found']) {
            Cache::put($cacheKey, $payload, now()->addDays(30));
        } else {
            Cache::put($cacheKey, $payload, now()->addHours(6));
        }

        return $payload;
    }

    /**
     * Turn coordinates into a human readable address.
     *
     * @return array{latitude: float, longitude: float, display_name: string|null, found: bool}
     */
    public function reverse(float $latitude, float $longitude): array
    {
        $cacheKey = 'reverse:'.sha1("{$latitude},{$longitude}");

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(10)
                ->get(config('maps.nominatim_reverse_url'), [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'jsonv2',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Nominatim reverse geocoding failed.', ['message' => $exception->getMessage()]);

            return ['latitude' => $latitude, 'longitude' => $longitude, 'display_name' => null, 'found' => false];
        }

        $result = $response->successful() ? $response->json() : null;

        $payload = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'display_name' => $result['display_name'] ?? null,
            'found' => $result !== null,
        ];

        Cache::put($cacheKey, $payload, now()->addDays(30));

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'User-Agent' => config('maps.nominatim_user_agent'),
        ];
    }

    /**
     * @return array{latitude: null, longitude: null, display_name: null, found: bool}
     */
    protected function empty(): array
    {
        return [
            'latitude' => null,
            'longitude' => null,
            'display_name' => null,
            'found' => false,
        ];
    }
}
