<?php

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FindRailRoute
{
    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     * @return array{coordinates: array<mixed>, distance_km: float}|null
     */
    public function handle(array $from, array $to): ?array
    {
        return Cache::remember('rails:'.implode(',', [...$from, ...$to]), now()->addDays(30), function () use ($from, $to): ?array {
            try {
                $response = Http::timeout(10)->get(config()->string('services.brouter.url').'/brouter', [
                    'lonlats' => implode(',', $from).'|'.implode(',', $to),
                    'profile' => 'rail',
                    'alternativeidx' => 0,
                    'format' => 'geojson',
                ]);
            } catch (ConnectionException) {
                return null;
            }

            $coordinates = $response->json('features.0.geometry.coordinates');
            $meters = $response->json('features.0.properties.track-length');

            if ($response->failed() || ! is_array($coordinates) || ! is_numeric($meters)) {
                return null;
            }

            return [
                'coordinates' => array_map(fn (array $point): array => array_slice($point, 0, 2), $coordinates),
                'distance_km' => (float) $meters / 1000,
            ];
        });
    }
}
