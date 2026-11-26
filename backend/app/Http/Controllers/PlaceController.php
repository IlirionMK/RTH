<?php

namespace App\Http\Controllers;

use App\Actions\SearchPlaces;
use App\Http\Requests\IndexPlaceRequest;
use App\Http\Resources\PlaceResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlaceController extends Controller
{
    public function index(IndexPlaceRequest $request, SearchPlaces $searchPlaces): AnonymousResourceCollection
    {
        return PlaceResource::collection(
            $searchPlaces->handle($request->string('query')->value(), app()->getLocale()),
        );
    }
}
