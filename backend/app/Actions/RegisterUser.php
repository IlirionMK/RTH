<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\EmailAlreadyRegistered;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    public function handle(string $name, string $email, string $password): void
    {
        $password = Hash::make($password);
        $user = User::where('email', $email)->first();

        if ($user?->hasVerifiedEmail()) {
            $user->notify(new EmailAlreadyRegistered);

            return;
        }

        $user ??= new User(['email' => $email]);
        $user->fill(['name' => $name, 'password' => $password])->save();
        $user->sendEmailVerificationNotification();
    }
}
