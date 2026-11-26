<?php

namespace App\Http\Controllers;

use App\Actions\RequestEmailChange;
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
    public function update(
        UpdateProfileRequest $request,
        #[CurrentUser] User $user,
        RequestEmailChange $requestEmailChange,
    ): UserResource {
        $user->update($request->safe()->except('email'));

        if ($request->safe()->has('email')) {
            $requestEmailChange->handle($user, $request->safe()->string('email')->value());
        }

        return UserResource::make($user);
    }
}
