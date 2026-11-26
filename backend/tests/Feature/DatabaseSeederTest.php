<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_fills_accounts_and_trips_to_look_at(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(status: 500)]);
        Sleep::fake();

        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->sole();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame(12, User::query()->count());
        $this->assertSame(1, User::query()->whereNotNull('blocked_at')->count());
        $this->assertSame(0, User::query()->where('email', 'unverified@example.com')->sole()->trips()->count());
        $this->assertGreaterThanOrEqual(33, Trip::query()->count());
        $this->assertSame(0, Point::query()->whereNull('country_code')->count());

        foreach (Trip::query()->withCount(['points', 'segments'])->get() as $trip) {
            $this->assertGreaterThanOrEqual(2, $trip->points_count);
            $this->assertSame($trip->points_count - 1, $trip->segments_count);
        }

        Sleep::assertSleptTimes(Segment::query()->where('transport', 'car')->count());
    }
}
