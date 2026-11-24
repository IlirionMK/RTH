<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class CreateToken
{
    public function handle(string $email, string $password, string $deviceName): NewAccessToken
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $user->createToken($deviceName);
    }
}
