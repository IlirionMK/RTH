<?php

namespace App\Actions;

use App\Support\CountryCode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ResolveCountry
{
    public function handle(float $latitude, float $longitude): ?string
    {
        return Cache::remember('countries:'.$latitude.','.$longitude, now()->addDays(30), function () use ($latitude, $longitude): ?string {
            try {
                $response = Http::withHeaders(['Authorization' => config()->string('services.openrouteservice.key')])
                    ->timeout(5)
                    ->get(config()->string('services.openrouteservice.url').'/pelias/v1/reverse', [
                        'point.lat' => $latitude,
                        'point.lon' => $longitude,
                        'layers' => 'country',
                        'size' => 1,
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            return $response->successful()
                ? CountryCode::fromAlpha3($response->json('features.0.properties.country_a'))
                : null;
        });
    }
}
