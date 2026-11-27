<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Trips')]
class TripController extends Controller
{
    #[QueryParameter('page', description: 'Page of the list, 15 items per page.', type: 'int', default: 1)]
    #[Endpoint(
        title: 'List trips',
        description: 'Returns the trips of the signed-in user, newest first, with the route, countries, transports and distance of each trip.',
    )]
    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        return TripResource::collection($user->trips()->withSummary()->latest('starts_on')->latest('id')->paginate());
    }

    #[Endpoint(
        title: 'Create trip',
        description: 'Creates a trip with a name, a start date and an optional end date and description.',
    )]
    public function store(StoreTripRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $trip = $user->trips()->create($request->validated());

        return TripResource::make($trip)->response()
            ->setStatusCode(201)
            ->header('Location', route('trips.show', $trip));
    }

    #[Endpoint(
        title: 'Show trip',
        description: 'Returns the trip with its points and segments in order. Each segment has a GeoJSON line and its distance. Trips of public profiles are readable for everyone, all trips for administrators.',
    )]
    public function show(Trip $trip): TripResource
    {
        Gate::authorize('view', $trip);

        return TripResource::make($trip->load(['points', 'segments']));
    }

    #[Endpoint(
        title: 'Update trip',
        description: 'Changes the name, dates or description. Only the owner can change the trip.',
        method: 'PATCH',
    )]
    public function update(UpdateTripRequest $request, Trip $trip): TripResource
    {
        $trip->update($request->validated());

        return TripResource::make($trip);
    }

    #[Endpoint(
        title: 'Delete trip',
        description: 'Deletes the trip with its points and segments. The owner and administrators can do this.',
    )]
    public function destroy(Trip $trip): Response
    {
        Gate::authorize('delete', $trip);

        $trip->delete();

        return response()->noContent();
    }
}
