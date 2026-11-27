<?php

namespace App\Http\Controllers;

use App\Actions\CollectStatistics;
use App\Http\Requests\ShowStatisticsRequest;
use App\Http\Resources\StatisticsResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;

#[Group('Maps and statistics')]
class StatisticsController extends Controller
{
    #[Endpoint(
        title: 'Statistics',
        description: 'Counts trips, visited countries and places and the distance in total, by transport and by year. Use `year` for one year. A trip counts in the year it started.',
    )]
    public function show(ShowStatisticsRequest $request, #[CurrentUser] User $user, CollectStatistics $collectStatistics): StatisticsResource
    {
        return StatisticsResource::make(
            $collectStatistics->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
