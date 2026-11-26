<?php

namespace App\Http\Controllers;

use App\Actions\CollectMapSegments;
use App\Http\Requests\ShowMapRequest;
use App\Http\Resources\MapResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class MapController extends Controller
{
    public function show(ShowMapRequest $request, #[CurrentUser] User $user, CollectMapSegments $collectMapSegments): MapResource
    {
        return MapResource::make(
            $collectMapSegments->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
