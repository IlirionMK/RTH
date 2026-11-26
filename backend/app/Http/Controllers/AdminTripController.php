<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexAdminTripRequest;
use App\Http\Resources\AdminTripResource;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminTripController extends Controller
{
    public function index(IndexAdminTripRequest $request): AnonymousResourceCollection
    {
        $like = '%'.$request->string('query')->value().'%';

        return AdminTripResource::collection(
            Trip::query()
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
