<?php

namespace App\Http\Controllers;

use App\Actions\CreateToken;
use App\Http\Requests\StoreTokenRequest;
use App\Http\Resources\TokenResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TokenController extends Controller
{
    /**
     * @unauthenticated
     */
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

    public function destroy(#[CurrentUser] User $user): Response
    {
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
