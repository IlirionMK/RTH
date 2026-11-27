<?php

namespace App\Http\Controllers;

use App\Actions\RequestEmailChange;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;

#[Group('Profile')]
class ProfileController extends Controller
{
    #[Endpoint(
        title: 'Show profile',
        description: 'Returns the signed-in user with the profile visibility, language and theme.',
    )]
    public function show(#[CurrentUser] User $user): UserResource
    {
        return UserResource::make($user);
    }

    #[Endpoint(
        title: 'Update profile',
        description: 'Changes the name, visibility, language or theme. A new email address is saved only after the user opens the link sent to it.',
        method: 'PATCH',
    )]
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
