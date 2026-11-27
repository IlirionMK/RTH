<?php

namespace App\Http\Controllers;

use App\Actions\RegisterUser;
use App\Http\Requests\StoreUserRequest;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Response;

#[Group('Auth')]
class UserController extends Controller
{
    /**
     * @unauthenticated
     */
    #[Endpoint(
        title: 'Register',
        description: 'Creates an account and sends a confirmation link to the email address. Always answers 202 without a body, so the answer does not show whether the address is already taken.',
    )]
    public function store(StoreUserRequest $request, RegisterUser $registerUser): Response
    {
        $registerUser->handle(
            $request->string('name')->value(),
            $request->string('email')->value(),
            $request->string('password')->value(),
        );

        return response()->noContent(202);
    }
}
