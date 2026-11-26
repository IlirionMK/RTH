<?php

namespace Database\Seeders;

use App\Actions\RouteSegment;
use App\Enums\Transport;
use App\Models\Point;
use App\Models\Trip;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Sleep;

class TripSeeder extends Seeder
{
    private const FLIGHT_KILOMETRES = 1200;

    private const CAR_ROUTE_PAUSE_MILLISECONDS = 1600;

    private const CITIES = [
        ['Berlin', 52.52, 13.405, 'DE'],
        ['Munich', 48.1351, 11.582, 'DE'],
        ['Hamburg', 53.5511, 9.9937, 'DE'],
        ['Cologne', 50.9375, 6.9603, 'DE'],
        ['Warsaw', 52.2297, 21.0122, 'PL'],
        ['Krakow', 50.0647, 19.945, 'PL'],
        ['Gdansk', 54.352, 18.6466, 'PL'],
        ['Wroclaw', 51.1079, 17.0385, 'PL'],
        ['Legnica', 51.207, 16.1553, 'PL'],
        ['Prague', 50.0755, 14.4378, 'CZ'],
        ['Vienna', 48.2082, 16.3738, 'AT'],
        ['Salzburg', 47.8095, 13.055, 'AT'],
        ['Budapest', 47.4979, 19.0402, 'HU'],
        ['Bratislava', 48.1486, 17.1077, 'SK'],
        ['Paris', 48.8566, 2.3522, 'FR'],
        ['Lyon', 45.764, 4.8357, 'FR'],
        ['Nice', 43.7102, 7.262, 'FR'],
        ['Strasbourg', 48.5734, 7.7521, 'FR'],
        ['Amsterdam', 52.3676, 4.9041, 'NL'],
        ['Brussels', 50.8503, 4.3517, 'BE'],
        ['Zurich', 47.3769, 8.5417, 'CH'],
        ['Geneva', 46.2044, 6.1432, 'CH'],
        ['Milan', 45.4642, 9.19, 'IT'],
        ['Venice', 45.4408, 12.3155, 'IT'],
        ['Florence', 43.7696, 11.2558, 'IT'],
        ['Rome', 41.9028, 12.4964, 'IT'],
        ['Naples', 40.8518, 14.2681, 'IT'],
        ['Barcelona', 41.3874, 2.1686, 'ES'],
        ['Madrid', 40.4168, -3.7038, 'ES'],
        ['Seville', 37.3891, -5.9845, 'ES'],
        ['Palma', 39.5696, 2.6502, 'ES'],
        ['Lisbon', 38.7223, -9.1393, 'PT'],
        ['Porto', 41.1579, -8.6291, 'PT'],
        ['Copenhagen', 55.6761, 12.5683, 'DK'],
        ['Stockholm', 59.3293, 18.0686, 'SE'],
        ['Oslo', 59.9139, 10.7522, 'NO'],
        ['Helsinki', 60.1699, 24.9384, 'FI'],
        ['Ljubljana', 46.0569, 14.5058, 'SI'],
        ['Zagreb', 45.815, 15.9819, 'HR'],
        ['Split', 43.5081, 16.4402, 'HR'],
        ['Athens', 37.9838, 23.7275, 'GR'],
        ['Vilnius', 54.6872, 25.2797, 'LT'],
        ['Riga', 56.9496, 24.1052, 'LV'],
        ['Dublin', 53.3498, -6.2603, 'IE'],
        ['London', 51.5074, -0.1278, 'GB'],
        ['Edinburgh', 55.9533, -3.1883, 'GB'],
    ];

    public function run(RouteSegment $routeSegment): void
    {
        foreach (User::query()->whereNotNull('email_verified_at')->get() as $user) {
            for ($count = fake()->numberBetween(3, 6); $count > 0; $count--) {
                $this->trip($user, $routeSegment);
            }
        }
    }

    private function trip(User $user, RouteSegment $routeSegment): void
    {
        $cities = $this->cities(fake()->numberBetween(2, 5));
        $startsOn = CarbonImmutable::instance(fake()->dateTimeBetween('2022-01-01'));
        $trip = Trip::factory()->for($user)->create([
            'name' => $cities[0][0].' to '.$cities[count($cities) - 1][0],
            'starts_on' => $startsOn,
            'ends_on' => fake()->boolean(80) ? $startsOn->addDays(fake()->numberBetween(1, 14)) : null,
            'created_at' => fake()->dateTimeBetween($user->created_at ?? '-1 year'),
        ]);

        $points = [];

        foreach ($cities as $index => [$name, $latitude, $longitude, $countryCode]) {
            $points[] = $trip->points()->create([
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country_code' => $countryCode,
                'position' => $index + 1,
            ]);
        }

        foreach (array_slice($points, 1) as $index => $to) {
            $segment = $trip->segments()->make([
                'from_point_id' => $points[$index]->id,
                'to_point_id' => $to->id,
                'transport' => $this->transport($points[$index], $to),
            ]);
            $routeSegment->handle($segment);

            if ($segment->transport === Transport::Car) {
                Sleep::for(self::CAR_ROUTE_PAUSE_MILLISECONDS)->milliseconds();
            }
        }
    }

    /**
     * @return list<array{string, float, float, string}>
     */
    private function cities(int $count): array
    {
        $route = [fake()->randomElement(self::CITIES)];

        while (count($route) < $count) {
            $last = $route[count($route) - 1];
            $rest = array_values(array_filter(self::CITIES, fn (array $city): bool => ! in_array($city, $route, true)));
            usort($rest, fn (array $a, array $b): int => $this->kilometres($last[1], $last[2], $a[1], $a[2])
                <=> $this->kilometres($last[1], $last[2], $b[1], $b[2]));
            $route[] = fake()->randomElement(fake()->boolean(15) ? $rest : array_slice($rest, 0, 5));
        }

        return $route;
    }

    private function transport(Point $from, Point $to): Transport
    {
        if ($this->kilometres($from->latitude, $from->longitude, $to->latitude, $to->longitude) > self::FLIGHT_KILOMETRES) {
            return Transport::Plane;
        }

        return fake()->randomElement([Transport::Car, Transport::Car, Transport::Car, Transport::Train, Transport::Train, Transport::Other]);
    }

    private function kilometres(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $haversine = sin(deg2rad($latitude2 - $latitude1) / 2) ** 2
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin(deg2rad($longitude2 - $longitude1) / 2) ** 2;

        return 6371 * 2 * asin(min(1.0, sqrt($haversine)));
    }
}
