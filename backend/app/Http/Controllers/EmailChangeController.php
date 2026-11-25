<?php

namespace App\Http\Controllers;

use App\Actions\ChangeEmail;
use App\Http\Requests\StoreEmailChangeRequest;
use App\Models\User;
use Illuminate\Http\Response;

class EmailChangeController extends Controller
{
    /**
     * @unauthenticated
     */
    public function store(StoreEmailChangeRequest $request, User $user, ChangeEmail $changeEmail): Response
    {
        $changeEmail->handle($user);

        return response()->noContent();
    }
}
