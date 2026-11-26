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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PointController extends Controller
{
    public function store(StorePointRequest $request, Trip $trip, AddPoint $addPoint): JsonResponse
    {
        $point = $addPoint->handle($trip, $request->validated());

        return PointResource::make($point)->response()
            ->setStatusCode(201)
            ->header('Location', route('points.show', $point));
    }

    public function show(Point $point): PointResource
    {
        Gate::authorize('view', $point->trip);

        return PointResource::make($point);
    }

    #[Endpoint(method: 'PATCH')]
    public function update(UpdatePointRequest $request, Point $point, UpdatePoint $updatePoint): PointResource
    {
        return PointResource::make($updatePoint->handle($point, $request->validated()));
    }

    public function destroy(Point $point, RemovePoint $removePoint): Response
    {
        Gate::authorize('update', $point->trip);

        $removePoint->handle($point);

        return response()->noContent();
    }
}
