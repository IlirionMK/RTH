<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Container\Attributes\CurrentUser;

class ProfileController extends Controller
{
    public function show(#[CurrentUser] User $user): UserResource
    {
        return UserResource::make($user);
    }

    #[Endpoint(method: 'PATCH')]
    public function update(UpdateProfileRequest $request, #[CurrentUser] User $user): UserResource
    {
        $user->update($request->validated());

        return UserResource::make($user);
    }
}
