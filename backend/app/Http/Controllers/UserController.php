<?php

namespace App\Http\Controllers;

use App\Actions\RegisterUser;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * @unauthenticated
     */
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
