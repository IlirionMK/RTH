<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function view(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id || $trip->user->hasPublicProfile() || $user->isAdmin();
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id;
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->id === $trip->user_id || $user->isAdmin();
    }
}
