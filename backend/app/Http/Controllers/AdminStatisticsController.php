<?php

namespace App\Http\Controllers;

use App\Actions\CollectSystemStatistics;
use App\Http\Resources\SystemStatisticsResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Admin')]
class AdminStatisticsController extends Controller
{
    #[Endpoint(
        title: 'System statistics',
        description: 'Counts accounts and trips and the new ones in each of the last 12 months.',
    )]
    public function show(CollectSystemStatistics $collectSystemStatistics): SystemStatisticsResource
    {
        return SystemStatisticsResource::make($collectSystemStatistics->handle());
    }
}
