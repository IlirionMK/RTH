<?php

namespace App\Http\Controllers;

use App\Actions\CollectSystemStatistics;
use App\Http\Resources\SystemStatisticsResource;

class AdminStatisticsController extends Controller
{
    public function show(CollectSystemStatistics $collectSystemStatistics): SystemStatisticsResource
    {
        return SystemStatisticsResource::make($collectSystemStatistics->handle());
    }
}
