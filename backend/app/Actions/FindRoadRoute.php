<?php

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FindRoadRoute
{
    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     * @return array{coordinates: array<mixed>, distance_km: float}|null
     */
    public function handle(array $from, array $to): ?array
    {
        return Cache::remember('roads:'.implode(',', [...$from, ...$to]), now()->addDays(30), function () use ($from, $to): ?array {
            try {
                $response = Http::withHeaders(['Authorization' => config()->string('services.openrouteservice.key')])
                    ->timeout(5)
                    ->post(config()->string('services.openrouteservice.url').'/openrouteservice/v2/directions/driving-car/geojson', [
                        'coordinates' => [$from, $to],
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            $coordinates = $response->json('features.0.geometry.coordinates');
            $meters = $response->json('features.0.properties.summary.distance');

            if ($response->failed() || ! is_array($coordinates) || ! is_numeric($meters)) {
                return null;
            }

            return ['coordinates' => $coordinates, 'distance_km' => (float) $meters / 1000];
        });
    }
}
