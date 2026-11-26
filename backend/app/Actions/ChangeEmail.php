<?php

namespace App\Actions;

use App\Models\User;

class ChangeEmail
{
    public function handle(User $user): void
    {
        abort_if(User::where('email', $user->pending_email)->exists(), 409, __('This email address is already in use.'));

        $user->forceFill([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();
    }
}
