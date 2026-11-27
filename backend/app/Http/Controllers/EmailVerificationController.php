<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailVerificationRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\Response;

#[Group('Auth')]
class EmailVerificationController extends Controller
{
    /**
     * @unauthenticated
     */
    #[PathParameter('hash', description: 'Hash from the signed link in the email.')]
    #[Endpoint(
        title: 'Confirm email address',
        description: 'Confirms the email address with the signed link from the registration email. After that the user can sign in.',
    )]
    public function store(StoreEmailVerificationRequest $request, User $user): Response
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->noContent();
    }
}
