<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_accounts_newest_first_with_trip_counts(): void
    {
        $admin = User::factory()->admin()->create(['created_at' => now()->subYear()]);
        $older = User::factory()->create(['name' => 'Older User', 'created_at' => now()->subMonth()]);
        $newer = User::factory()->blocked()->create(['name' => 'Newer User', 'email' => 'newer@example.com', 'created_at' => now()->subDay()]);
        Trip::factory()->for($older)->count(2)->create();
        Sanctum::actingAs($admin);

        $this->getJson(route('admin.users.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.0.email', 'newer@example.com')
            ->assertJsonPath('data.0.role', 'user')
            ->assertJsonPath('data.0.is_blocked', true)
            ->assertJsonPath('data.0.trips_count', 0)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('data.1.is_blocked', false)
            ->assertJsonPath('data.1.blocked_at', null)
            ->assertJsonPath('data.1.trips_count', 2)
            ->assertJsonPath('data.2.role', 'admin')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_finds_accounts_by_name_or_email(): void
    {
        $byName = User::factory()->create(['name' => 'Anna Test', 'email' => 'anna@example.com']);
        $byEmail = User::factory()->create(['name' => 'Other User', 'email' => 'test.other@example.com']);
        User::factory()->create(['name' => 'Unknown User', 'email' => 'unknown@example.com']);
        Sanctum::actingAs(User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@example.com']));

        $ids = $this->getJson(route('admin.users.index', ['query' => 'TEST']))
            ->assertOk()
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing([$byName->id, $byEmail->id], $ids);
    }

    public function test_admin_blocks_account_and_its_sessions_end(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson(route('admin.users.update', $user), ['is_blocked' => true])
            ->assertOk()
            ->assertJsonPath('data.is_blocked', true);

        $this->assertNotNull($user->refresh()->blocked_at);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson(route('profile.show'))->assertUnauthorized();
    }

    public function test_admin_unblocks_account(): void
    {
        $user = User::factory()->blocked()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson(route('admin.users.update', $user), ['is_blocked' => false])
            ->assertOk()
            ->assertJsonPath('data.is_blocked', false)
            ->assertJsonPath('data.blocked_at', null);

        $this->assertNull($user->refresh()->blocked_at);
    }

    public function test_admin_changes_role(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson(route('admin.users.update', $user), ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->assertTrue($user->refresh()->isAdmin());
    }

    public function test_role_is_user_or_admin(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson(route('admin.users.update', $user), ['role' => 'owner', 'is_blocked' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role', 'is_blocked']);
    }

    public function test_admin_cannot_block_self_or_change_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->patchJson(route('admin.users.update', $admin), ['role' => 'user', 'is_blocked' => true], ['Accept-Language' => 'pl'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.role.0', 'Nie możesz zmienić własnej roli.')
            ->assertJsonPath('errors.is_blocked.0', 'Nie możesz zablokować własnego konta.');

        $admin->refresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertNull($admin->blocked_at);
    }

    public function test_regular_user_cannot_use_admin_functions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('admin.users.index'))->assertForbidden();
        $this->patchJson(route('admin.users.update', $user), ['role' => 'admin'])->assertForbidden();
        $this->getJson(route('admin.trips.index'))->assertForbidden();
        $this->getJson(route('admin.statistics.show'))->assertForbidden();

        $this->assertFalse($user->refresh()->isAdmin());
    }

    public function test_guest_cannot_use_admin_functions(): void
    {
        $this->getJson(route('admin.users.index'))->assertUnauthorized();
        $this->getJson(route('admin.statistics.show'))->assertUnauthorized();
    }
}
