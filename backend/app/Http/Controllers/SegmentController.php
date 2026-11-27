<?php

namespace App\Http\Controllers;

use App\Actions\RouteSegment;
use App\Http\Requests\UpdateSegmentRequest;
use App\Http\Resources\SegmentResource;
use App\Models\Segment;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Trips')]
class SegmentController extends Controller
{
    #[Endpoint(
        title: 'Change transport',
        description: 'Sets the transport of the segment and draws its route again: car by road, train by rail, plane as an arc, other as a straight line. A car or train without a found route gets a straight line marked as approximate.',
        method: 'PATCH',
    )]
    public function update(
        UpdateSegmentRequest $request,
        Segment $segment,
        RouteSegment $routeSegment,
    ): SegmentResource {
        $routeSegment->handle($segment->fill($request->validated()));

        return SegmentResource::make($segment);
    }
}
