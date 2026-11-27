<?php

namespace App\Http\Controllers;

use App\Actions\AddPoint;
use App\Actions\RemovePoint;
use App\Actions\UpdatePoint;
use App\Http\Requests\StorePointRequest;
use App\Http\Requests\UpdatePointRequest;
use App\Http\Resources\PointResource;
use App\Models\Point;
use App\Models\Trip;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Trips')]
class PointController extends Controller
{
    #[Endpoint(
        title: 'Add point',
        description: 'Adds a place to the trip, at the end or at the given position. The API finds the country of the point and rebuilds the segments around it.',
    )]
    public function store(StorePointRequest $request, Trip $trip, AddPoint $addPoint): JsonResponse
    {
        $point = $addPoint->handle($trip, $request->validated());

        return PointResource::make($point)->response()
            ->setStatusCode(201)
            ->header('Location', route('points.show', $point));
    }

    #[Endpoint(
        title: 'Show point',
        description: 'Returns one point of a trip that the user can read.',
    )]
    public function show(Point $point): PointResource
    {
        Gate::authorize('view', $point->trip);

        return PointResource::make($point);
    }

    #[Endpoint(
        title: 'Update point',
        description: 'Renames the point or moves it to new coordinates or a new position. New coordinates change the country and the routes of the neighbouring segments.',
        method: 'PATCH',
    )]
    public function update(UpdatePointRequest $request, Point $point, UpdatePoint $updatePoint): PointResource
    {
        return PointResource::make($updatePoint->handle($point, $request->validated()));
    }

    #[Endpoint(
        title: 'Remove point',
        description: 'Removes the point and joins its neighbours with a new segment.',
    )]
    public function destroy(Point $point, RemovePoint $removePoint): Response
    {
        Gate::authorize('update', $point->trip);

        $removePoint->handle($point);

        return response()->noContent();
    }
}
