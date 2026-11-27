<?php

namespace App\Http\Controllers;

use App\Actions\SearchPlaces;
use App\Http\Requests\IndexPlaceRequest;
use App\Http\Resources\PlaceResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Places')]
class PlaceController extends Controller
{
    #[Endpoint(
        title: 'Search places',
        description: 'Finds up to 5 places by name with their label, country code and coordinates. Results are cached for 30 days. Answers 503 when the search service does not answer.',
    )]
    public function index(IndexPlaceRequest $request, SearchPlaces $searchPlaces): AnonymousResourceCollection
    {
        return PlaceResource::collection(
            $searchPlaces->handle($request->string('query')->value(), app()->getLocale()),
        );
    }
}
