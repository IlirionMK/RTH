<?php

namespace App\Http\Controllers;

use App\Actions\CollectStatistics;
use App\Http\Requests\ShowStatisticsRequest;
use App\Http\Resources\StatisticsResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class StatisticsController extends Controller
{
    public function show(ShowStatisticsRequest $request, #[CurrentUser] User $user, CollectStatistics $collectStatistics): StatisticsResource
    {
        return StatisticsResource::make(
            $collectStatistics->handle($user, $request->filled('year') ? $request->integer('year') : null),
        );
    }
}
