<?php

namespace App\Http\Controllers;

use App\Actions\CollectStatistics;
use App\Http\Requests\ShowStatisticsRequest;
use App\Http\Resources\StatisticsResource;
use App\Models\User;

class PublicProfileStatisticsController extends Controller
{
    public function show(ShowStatisticsRequest $request, User $user, CollectStatistics $collectStatistics): StatisticsResource
    {
        return StatisticsResource::make(
            $collectStatistics->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
