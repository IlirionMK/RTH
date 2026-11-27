<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexAdminTripRequest;
use App\Http\Resources\AdminTripResource;
use App\Models\Trip;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Admin')]
class AdminTripController extends Controller
{
    #[QueryParameter('page', description: 'Page of the list, 15 items per page.', type: 'int', default: 1)]
    #[Endpoint(
        title: 'List all trips',
        description: 'Returns the trips of all users, newest first, with author, countries and distance. Use `query` to search by trip name or author.',
    )]
    public function index(IndexAdminTripRequest $request): AnonymousResourceCollection
    {
        $like = '%'.$request->string('query')->value().'%';

        return AdminTripResource::collection(
            Trip::query()
                ->withSummary()
                ->with('user:id,name,email')
                ->when($request->filled('query'), fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query->whereLike('name', $like)->orWhereHas(
                        'user',
                        fn (Builder $query) => $query->whereLike('name', $like)->orWhereLike('email', $like),
                    ),
                ))
                ->latest()
                ->latest('id')
                ->paginate(),
        );
    }
}
