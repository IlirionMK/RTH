<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

#[Group('Profile')]
class PasswordController extends Controller
{
    #[Endpoint(
        title: 'Change password',
        description: 'Sets a new password. The current password is required.',
    )]
    public function update(UpdatePasswordRequest $request, #[CurrentUser] User $user): Response
    {
        $user->update(['password' => $request->validated('password')]);

        return response()->noContent();
    }
}
