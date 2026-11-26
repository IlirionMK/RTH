<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPublicProfileRequest;
use App\Http\Resources\PublicProfileResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicProfileController extends Controller
{
    public function index(IndexPublicProfileRequest $request): AnonymousResourceCollection
    {
        return PublicProfileResource::collection(
            User::query()
                ->where('is_public', true)
                ->whereLike('name', '%'.$request->string('query')->value().'%')
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(),
        );
    }

    public function show(User $user): PublicProfileResource
    {
        return PublicProfileResource::make($user);
    }
}
