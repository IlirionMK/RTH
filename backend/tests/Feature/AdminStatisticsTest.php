<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_count_accounts_and_trips_by_month_for_a_year(): void
    {
        $this->travelTo('2026-11-15 12:00:00');
        $admin = User::factory()->admin()->create(['created_at' => '2025-11-30 23:00:00']);
        $march = User::factory()->create(['created_at' => '2026-03-10 08:00:00']);
        User::factory()->create(['created_at' => '2026-11-01 00:00:00']);
        Trip::factory()->for($march)->count(2)->create(['created_at' => '2026-03-11 08:00:00']);
        Trip::factory()->for($admin)->create(['created_at' => '2025-12-01 00:00:00']);
        Sanctum::actingAs($admin);

        $response = $this->getJson(route('admin.statistics.show'))
            ->assertOk()
            ->assertJsonPath('data.users_count', 3)
            ->assertJsonPath('data.trips_count', 3)
            ->assertJsonCount(12, 'data.months')
            ->assertJsonPath('data.months.0', ['month' => '2025-12', 'users' => 0, 'trips' => 1])
            ->assertJsonPath('data.months.3', ['month' => '2026-03', 'users' => 1, 'trips' => 2])
            ->assertJsonPath('data.months.11', ['month' => '2026-11', 'users' => 1, 'trips' => 0]);

        $this->assertSame(['users' => 2, 'trips' => 3], [
            'users' => array_sum(array_column($response->json('data.months'), 'users')),
            'trips' => array_sum(array_column($response->json('data.months'), 'trips')),
        ]);
    }
}
