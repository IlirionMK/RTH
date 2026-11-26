<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSegmentRequest;
use App\Http\Resources\SegmentResource;
use App\Models\Segment;
use Dedoc\Scramble\Attributes\Endpoint;

class SegmentController extends Controller
{
    #[Endpoint(method: 'PATCH')]
    public function update(UpdateSegmentRequest $request, Segment $segment): SegmentResource
    {
        $segment->update($request->validated());

        return SegmentResource::make($segment);
    }
}
