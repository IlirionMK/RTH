<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailVerificationRequest;
use App\Models\User;
use Illuminate\Http\Response;

class EmailVerificationController extends Controller
{
    /**
     * @unauthenticated
     */
    public function store(StoreEmailVerificationRequest $request, User $user): Response
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->noContent();
    }
}
