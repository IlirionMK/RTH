<?php

namespace App\Actions;

use App\Support\CountryCode;
use App\Support\Place;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SearchPlaces
{
    /**
     * @return list<Place>
     */
    public function handle(string $query, string $locale): array
    {
        try {
            $features = Cache::remember(
                'places:'.$locale.':'.Str::lower($query),
                now()->addDays(30),
                fn (): array => Http::withHeaders(['Authorization' => config()->string('services.openrouteservice.key')])
                    ->timeout(5)
                    ->get(config()->string('services.openrouteservice.url').'/pelias/v1/search', [
                        'text' => $query,
                        'size' => 5,
                        'lang' => $locale,
                    ])
                    ->throw()
                    ->collect('features')
                    ->all(),
            );
        } catch (ConnectionException|RequestException) {
            abort(503, __('Place search is not available right now. Try again later.'));
        }

        return array_values(array_map(fn (mixed $feature): Place => new Place(
            name: (string) data_get($feature, 'properties.name'),
            label: (string) data_get($feature, 'properties.label'),
            countryCode: CountryCode::fromAlpha3(data_get($feature, 'properties.country_a')),
            latitude: (float) data_get($feature, 'geometry.coordinates.1'),
            longitude: (float) data_get($feature, 'geometry.coordinates.0'),
        ), $features));
    }
}
