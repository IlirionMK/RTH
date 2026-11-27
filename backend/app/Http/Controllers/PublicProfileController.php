<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPublicProfileRequest;
use App\Http\Resources\PublicProfileResource;
use App\Models\Point;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicProfileController extends Controller
{
    public function index(IndexPublicProfileRequest $request): AnonymousResourceCollection
    {
        return PublicProfileResource::collection(
            User::query()
                ->withPublicProfile()
                ->when($request->filled('query'), fn (Builder $query) => $query->whereLike('name', '%'.$request->string('query')->value().'%'))
                ->withCount('trips')
                ->addSelect(['countries_count' => Point::query()
                    ->selectRaw('count(distinct country_code)')
                    ->join('trips', 'trips.id', '=', 'points.trip_id')
                    ->whereColumn('trips.user_id', 'users.id')])
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
