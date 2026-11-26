<?php

namespace App\Http\Controllers;

use App\Actions\UpdateAccount;
use App\Http\Requests\IndexAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function index(IndexAdminUserRequest $request): AnonymousResourceCollection
    {
        $like = '%'.$request->string('query')->value().'%';

        return AdminUserResource::collection(
            User::query()
                ->withCount('trips')
                ->when($request->filled('query'), fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query->whereLike('name', $like)->orWhereLike('email', $like),
                ))
                ->latest()
                ->latest('id')
                ->paginate(),
        );
    }

    #[Endpoint(method: 'PATCH')]
    public function update(UpdateAdminUserRequest $request, User $user, UpdateAccount $updateAccount): AdminUserResource
    {
        return AdminUserResource::make($updateAccount->handle($user, $request->validated())->loadCount('trips'));
    }
}
