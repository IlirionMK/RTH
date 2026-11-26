<?php

namespace App\Http\Controllers;

use App\Actions\RouteSegment;
use App\Http\Requests\UpdateSegmentRequest;
use App\Http\Resources\SegmentResource;
use App\Models\Segment;
use Dedoc\Scramble\Attributes\Endpoint;

class SegmentController extends Controller
{
    #[Endpoint(method: 'PATCH')]
    public function update(
        UpdateSegmentRequest $request,
        Segment $segment,
        RouteSegment $routeSegment,
    ): SegmentResource {
        $routeSegment->handle($segment->fill($request->validated()));

        return SegmentResource::make($segment);
    }
}
