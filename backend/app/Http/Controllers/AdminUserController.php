<?php

namespace App\Http\Controllers;

use App\Actions\UpdateAccount;
use App\Http\Requests\IndexAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Admin')]
class AdminUserController extends Controller
{
    #[QueryParameter('page', description: 'Page of the list, 15 items per page.', type: 'int', default: 1)]
    #[Endpoint(
        title: 'List accounts',
        description: 'Returns all accounts, newest first, with role, block status and number of trips. Filter by `query` (name or email), `role` and `status`.',
    )]
    public function index(IndexAdminUserRequest $request): AnonymousResourceCollection
    {
        $like = '%'.$request->string('query')->value().'%';

        return AdminUserResource::collection(
            User::query()
                ->withCount('trips')
                ->when($request->filled('query'), fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query->whereLike('name', $like)->orWhereLike('email', $like),
                ))
                ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->string('role')->value()))
                ->when($request->input('status') === 'active', fn (Builder $query) => $query->whereNull('blocked_at'))
                ->when($request->input('status') === 'blocked', fn (Builder $query) => $query->whereNotNull('blocked_at'))
                ->latest()
                ->latest('id')
                ->paginate(),
        );
    }

    #[Endpoint(
        title: 'Update account',
        description: 'Changes the role or blocks the account. Blocking signs the user out on all devices. Administrators cannot change their own account.',
        method: 'PATCH',
    )]
    public function update(UpdateAdminUserRequest $request, User $user, UpdateAccount $updateAccount): AdminUserResource
    {
        return AdminUserResource::make($updateAccount->handle($user, $request->validated())->loadCount('trips'));
    }
}
