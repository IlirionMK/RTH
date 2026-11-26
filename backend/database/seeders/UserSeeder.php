<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_public' => true,
            'created_at' => now()->subMonths(11)->startOfMonth(),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_public' => true,
            'created_at' => now()->subMonths(10),
        ]);

        User::factory()
            ->count(8)
            ->sequence(fn (Sequence $sequence): array => ['is_public' => $sequence->index % 2 === 0])
            ->state(fn (): array => [
                'email' => fake()->unique()->userName().'@example.com',
                'created_at' => fake()->dateTimeBetween('-11 months'),
            ])
            ->create();

        User::factory()->blocked()->create([
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'is_public' => true,
            'created_at' => now()->subMonths(6),
        ]);

        User::factory()->unverified()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'created_at' => now()->subWeek(),
        ]);
    }
}
