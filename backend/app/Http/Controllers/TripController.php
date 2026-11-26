<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TripController extends Controller
{
    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        return TripResource::collection($user->trips()->latest('starts_on')->latest('id')->paginate());
    }

    public function store(StoreTripRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $trip = $user->trips()->create($request->validated());

        return TripResource::make($trip)->response()
            ->setStatusCode(201)
            ->header('Location', route('trips.show', $trip));
    }

    public function show(Trip $trip): TripResource
    {
        Gate::authorize('view', $trip);

        return TripResource::make($trip->load(['points', 'segments']));
    }

    #[Endpoint(method: 'PATCH')]
    public function update(UpdateTripRequest $request, Trip $trip): TripResource
    {
        $trip->update($request->validated());

        return TripResource::make($trip);
    }

    public function destroy(Trip $trip): Response
    {
        Gate::authorize('delete', $trip);

        $trip->delete();

        return response()->noContent();
    }
}
