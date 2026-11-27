<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPublicProfileRequest;
use App\Http\Resources\PublicProfileResource;
use App\Models\Point;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Public profiles')]
class PublicProfileController extends Controller
{
    #[QueryParameter('page', description: 'Page of the list, 15 items per page.', type: 'int', default: 1)]
    #[Endpoint(
        title: 'List public profiles',
        description: 'Returns users with a public profile, sorted by name, with their numbers of trips and countries. Use `query` to search by name.',
    )]
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

    #[Endpoint(
        title: 'Show public profile',
        description: 'Returns the name of a user with a public profile. A private or blocked profile answers 404, like a missing one.',
    )]
    public function show(User $user): PublicProfileResource
    {
        return PublicProfileResource::make($user);
    }
}
