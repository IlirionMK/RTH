<?php

namespace App\Http\Controllers;

use App\Actions\CollectStatistics;
use App\Http\Requests\ShowStatisticsRequest;
use App\Http\Resources\StatisticsResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Public profiles')]
class PublicProfileStatisticsController extends Controller
{
    #[Endpoint(
        title: 'Statistics of a public profile',
        description: 'Returns the statistics of a user with a public profile, like `GET /statistics`.',
    )]
    public function show(ShowStatisticsRequest $request, User $user, CollectStatistics $collectStatistics): StatisticsResource
    {
        return StatisticsResource::make(
            $collectStatistics->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
