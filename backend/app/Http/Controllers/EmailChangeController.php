<?php

namespace App\Http\Controllers;

use App\Actions\ChangeEmail;
use App\Http\Requests\StoreEmailChangeRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\Response;

#[Group('Profile')]
class EmailChangeController extends Controller
{
    /**
     * @unauthenticated
     */
    #[PathParameter('hash', description: 'Hash from the signed link in the email.')]
    #[Endpoint(
        title: 'Confirm new email address',
        description: 'Confirms the new email address with the signed link sent to it. Answers 409 if another account took the address in the meantime.',
    )]
    public function store(StoreEmailChangeRequest $request, User $user, ChangeEmail $changeEmail): Response
    {
        $changeEmail->handle($user);

        return response()->noContent();
    }
}
