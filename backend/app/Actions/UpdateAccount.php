<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAccount
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            if (array_key_exists('role', $attributes)) {
                $user->role = Role::from((string) $attributes['role']);
            }

            if (array_key_exists('is_blocked', $attributes)) {
                $user->blocked_at = $attributes['is_blocked'] ? ($user->blocked_at ?? now()) : null;
            }

            $user->save();

            if ($user->blocked_at !== null) {
                $user->tokens()->delete();
            }

            return $user;
        });
    }
}
