<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\EmailAlreadyRegistered;
use App\Notifications\VerifyNewEmail;
use Illuminate\Support\Facades\Notification;

class RequestEmailChange
{
    public function handle(User $user, string $email): void
    {
        if ($email === $user->email) {
            $user->update(['pending_email' => null]);

            return;
        }

        $user->update(['pending_email' => $email]);

        if (User::where('email', $email)->exists()) {
            Notification::route('mail', $email)->notify(new EmailAlreadyRegistered);

            return;
        }

        Notification::route('mail', $email)->notify(new VerifyNewEmail($user));
    }
}
