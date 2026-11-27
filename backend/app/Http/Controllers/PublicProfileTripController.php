<?php

namespace App\Http\Controllers;

use App\Http\Resources\TripResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Public profiles')]
class PublicProfileTripController extends Controller
{
    #[QueryParameter('page', description: 'Page of the list, 15 items per page.', type: 'int', default: 1)]
    #[Endpoint(
        title: 'Trips of a public profile',
        description: 'Returns the trips of a user with a public profile, newest first. Open a trip with `GET /trips/{trip}`.',
    )]
    public function index(User $user): AnonymousResourceCollection
    {
        return TripResource::collection($user->trips()->withSummary()->latest('starts_on')->latest('id')->paginate());
    }
}
