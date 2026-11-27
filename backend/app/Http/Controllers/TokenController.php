<?php

namespace App\Http\Controllers;

use App\Actions\CreateToken;
use App\Http\Requests\StoreTokenRequest;
use App\Http\Resources\TokenResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

#[Group('Auth')]
class TokenController extends Controller
{
    /**
     * @unauthenticated
     */
    #[Endpoint(
        title: 'Sign in',
        description: 'Checks the email and password and returns a Bearer token. A blocked account or an unconfirmed email address gets 422 with the reason on the `email` field.',
    )]
    public function store(StoreTokenRequest $request, CreateToken $createToken): JsonResponse
    {
        $token = $createToken->handle(
            $request->string('email')->value(),
            $request->string('password')->value(),
            $request->string('device_name')->value(),
        );

        return TokenResource::make($token)
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tokens.destroy'));
    }

    #[Endpoint(
        title: 'Sign out',
        description: 'Revokes the token of this request. Tokens on other devices stay valid.',
    )]
    public function destroy(#[CurrentUser] User $user): Response
    {
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
