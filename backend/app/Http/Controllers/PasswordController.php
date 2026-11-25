<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request, #[CurrentUser] User $user): Response
    {
        $user->update(['password' => $request->validated('password')]);

        return response()->noContent();
    }
}
