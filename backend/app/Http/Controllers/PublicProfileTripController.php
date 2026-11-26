<?php

namespace App\Http\Controllers;

use App\Http\Resources\TripResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicProfileTripController extends Controller
{
    public function index(User $user): AnonymousResourceCollection
    {
        return TripResource::collection($user->trips()->withSummary()->latest('starts_on')->latest('id')->paginate());
    }
}
