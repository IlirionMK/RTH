<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowMapRequest;
use App\Http\Resources\MapResource;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class MapController extends Controller
{
    public function show(ShowMapRequest $request, #[CurrentUser] User $user): MapResource
    {
        $trips = $user->trips()->startedIn($request->filled('year') ? $request->integer('year') : null);

        return MapResource::make(
            Segment::query()
                ->select(['id', 'trip_id', 'transport', 'distance_km', 'map_geometry'])
                ->whereIn('trip_id', $trips->select('id'))
                ->with('trip:id,name')
                ->orderBy('trip_id')
                ->orderBy('id')
                ->get(),
        );
    }
}
