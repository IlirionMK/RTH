<?php

namespace App\Http\Controllers;

use App\Actions\CollectMapSegments;
use App\Http\Requests\ShowMapRequest;
use App\Http\Resources\MapResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;

#[Group('Maps and statistics')]
class MapController extends Controller
{
    #[Endpoint(
        title: 'Map of all trips',
        description: 'Returns one GeoJSON FeatureCollection with a simplified line for every segment of the trips of the user. Use `year` to show only the trips that started in that year.',
    )]
    public function show(ShowMapRequest $request, #[CurrentUser] User $user, CollectMapSegments $collectMapSegments): MapResource
    {
        return MapResource::make(
            $collectMapSegments->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
