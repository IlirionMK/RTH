<?php

namespace App\Http\Controllers;

use App\Actions\CollectMapSegments;
use App\Http\Requests\ShowMapRequest;
use App\Http\Resources\MapResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Public profiles')]
class PublicProfileMapController extends Controller
{
    #[Endpoint(
        title: 'Map of a public profile',
        description: 'Returns the routes of all trips of a user with a public profile, like `GET /map`.',
    )]
    public function show(ShowMapRequest $request, User $user, CollectMapSegments $collectMapSegments): MapResource
    {
        return MapResource::make(
            $collectMapSegments->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
